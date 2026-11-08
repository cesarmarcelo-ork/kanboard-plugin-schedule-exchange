#!/usr/bin/env python3
"""Build and validate a minimal, installable Schedule Exchange plugin ZIP."""
import argparse
import re
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SOURCES = ["Plugin.php", "Controller", "Db", "Exporter", "Helper", "Locale", "Model", "Template", "Asset", "Assets"]
REQUIRED = [
    "Plugin.php",
    "Controller/ScheduleExchangeController.php",
    "Controller/PlanningDependencyController.php",
    "Model/PlanningDependencyModel.php",
    "Exporter/ProjectXmlExporter.php",
    "Template/task/dependencies.php",
]
VERSION = "0.1.0-rc1"

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("output", type=Path)
    args = parser.parse_args()
    plugin = (ROOT / "Plugin.php").read_text(encoding="utf-8")
    if "namespace Kanboard\\Plugin\\ScheduleExchange;" not in plugin:
        raise RuntimeError("Wrong plugin namespace")
    if f"return '{VERSION}';" not in plugin:
        raise RuntimeError("Version metadata mismatch")
    files = {}
    for entry in SOURCES:
        path = ROOT / entry
        if not path.exists():
            continue
        if path.is_file():
            files[entry] = path
            continue
        for file in path.rglob("*"):
            if file.is_file():
                relative = file.relative_to(ROOT)
                if any(part.startswith(".") for part in relative.parts):
                    continue
                files[relative.as_posix()] = file
    for name in REQUIRED:
        if name not in files:
            raise RuntimeError(f"Missing required plugin file: {name}")
    for name in files:
        if name.endswith(".php"):
            contents = files[name].read_text(encoding="utf-8")
            if "namespace Kanboard\\Plugin\\ScheduleExport" in contents:
                raise RuntimeError(f"Legacy namespace in {name}")
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(args.output, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        for name in sorted(files):
            archive.write(files[name], arcname="ScheduleExchange/" + name)
    with zipfile.ZipFile(args.output) as archive:
        entries = archive.namelist()
        if len(entries) != len(files) or len(set(entries)) != len(entries):
            raise RuntimeError("Archive has missing or duplicate entries")
        if any(not entry.startswith("ScheduleExchange/") for entry in entries):
            raise RuntimeError("Plugin ZIP has incorrect root directory")
        if any(".git" in entry or "/tests/" in entry or "/docs/" in entry for entry in entries):
            raise RuntimeError("Developer artifacts found in distribution")
        assert archive.testzip() is None
    print(f"PASS: {args.output.name}: {len(files)} installation files, plugin root and identity validated")

if __name__ == "__main__":
    main()
