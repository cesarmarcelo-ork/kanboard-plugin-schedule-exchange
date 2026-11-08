# Kanboard Schedule Exchange

Kanboard plugin for **native subtask planning**, **task/subtask planning dependencies**, and **Microsoft Project XML (MSPDI) export**.

**Status:** v0.1.0 stable publication candidate, functionally accepted and ready for the GitHub release process. The previous Schedule Export 0.6.1 is a separate, superseded codebase; the stable tag/release must be published before calling distribution complete.

**Compatibility:** Kanboard >= 1.2.50. The GitHub Actions matrix tests Kanboard 1.2.50 and 1.2.57 and PHP 8.1–8.4.

## Scope and responsibilities

- Kanboard remains the source of truth for cards, subtasks, original dates, estimates, completion and registered relationships.
- Schedule Exchange stores planning information and four dependency endpoint combinations: **card → card**, **card → subtask**, **subtask → card**, and **subtask → subtask**.
- Relationship types **FS, SS, FF and SF** and integer positive/negative lags are represented in MSPDI.
- Schedule Exchange **does not recalculate, level resources, or automatically move dates**. Scheduling engines and Gantt applications interpret the exported project independently.
- XML export is read-only; it never writes back recalculated dates.

## Installation and safe migration

1. Back up the current plugin directory and the Kanboard database.
2. Install this repository into `plugins/ScheduleExchange` using the exact directory name, **not** `plugins/ScheduleExport`.
3. Do **not** enable Schedule Export alongside Schedule Exchange: they share legacy planning features and database objects; coexistence was not homologated.
4. Verify the installed commit and the Kanboard plugin screen, then test the XML export in a disposable project.

See [migration and rollback guidance](MIGRACAO.md).

## User workflow

1. Create or open cards and native subtasks.
2. Set original card dates and, where required, independent planned subtask periods.
3. In a card's **Planning dependencies** panel, select a predecessor from the current project and a successor belonging to the open card or one of its subtasks.
4. Choose FS / SS / FF / SF and lag in days. The plugin rejects invalid activity ownership, duplicates and cycles in stored Schedule Exchange dependencies.
5. Export the authorized project via **Export project schedule (.xml)** (detailed native-subtask export is the default).
6. Open the file in ProjectLibre or another MSPDI-capable planner.

**Important:** when a card is exported as an MSPDI summary activity because it owns children, explicit card-to-card or card-to-subtask dependencies are attached to a separate **original card period** row, not to the summary row. A summary card without a usable original planned start cannot safely serve as a dependency endpoint; export rejects such an ambiguous relationship instead of silently dropping it. Dependencies involving subtasks are not silently removed in `?subtasks=legacy` mode: that format must not be used for such a project.

## Interoperability verification

A functional acceptance exercise in private Kanboard project #44, using two cards and one subtask per card, confirmed the following in **ProjectLibre**:

| Source → target | Test | ProjectLibre predecessor field |
|---|---|---|
| Subtask A.1 → subtask B.1 | FS/0, SS/0, FF/0, SF/0 | 3FS, 3SS, 3FF, 3SF |
| Card A → card B | FS/0; SS/+1 day | 2; 2SS+1 day |
| Subtask A.1 → subtask B.1 | FS/-1 day | 3FS-1 day |
| Card A → subtask B.1 | FS/0 | 2 |
| Subtask A.1 → card B | FS/0 | 3 |

These acceptance observations **do not** certify all schedulers, all calendars, all date-lag policies or arbitrary project sizes. Imported duration presentation differs among ProjectLibre, Open Planner Studio and other consumers; MSPDI exports use a 24x7 source-timestamp calendar, and the importer may show days, hours or its own recalculated schedule. These are planning application responsibilities, not automatic Kanboard changes.

## Tests

```bash
php tests/test_dependency_endpoints.php
php tests/test_dependency_export_mspdi.php
php tests/test_native_plans_v040.php
python3 tests/verify_plugin_rename.py
```

GitHub Actions additionally runs source timestamp, XML, date, conversion, permission, translation and Kanboard integration tests. Private project exports, credentials and server backups are not stored in this repository.

[Português (Brasil)](README-PTBR.md) · [Migration/rollback](MIGRACAO.md)

License: MIT. Maintained by OrkFlowTech.
