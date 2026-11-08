# Schedule Exchange - functional acceptance and remaining release gates

## Evidence and scope

The **TESTE SUBTAREFAS** project (#44, private homelab Kanboard, November 2026) used cards A/B and native subtasks A.1/B.1. Functional XML exports were opened in ProjectLibre and inspected with the predecessor column and Gantt arrows. These are **manual acceptance observations**; project files themselves remain private and are not committed.

| Acceptance test | Relationship | Observed predecessor field | Outcome |
|---|---|---|---|
| A.1 -> B.1 | FS/0 | 3FS | Pass |
| A.1 -> B.1 | SS/0 | 3SS | Pass |
| A.1 -> B.1 | FF/0 | 3FF | Pass |
| A.1 -> B.1 | SF/0 | 3SF | Pass |
| Card A -> card B | FS/0 | 2 | Pass |
| Card A -> card B | SS/+1 day | 2SS+1 day | Pass |
| A.1 -> B.1 | FS/-1 day | 3FS-1 day | Pass |
| Card A -> B.1 | FS/0 | 2 | Pass |
| A.1 -> card B | FS/0 | 3 | Pass |

Card summary entities were represented by their original-period auxiliary rows in MSPDI. For these fixtures, subtask A.1 = XML ID 3, card A original period = ID 2, card B original period = ID 6 and subtask B.1 = ID 7. XML UIDs are separate from visible ProjectLibre IDs.

## Automated checks

- `tests/test_dependency_endpoints.php`: endpoint type combinations, project membership, duplicates, circular registration and deletion.
- `tests/test_dependency_export_mspdi.php`: four relationship types, lag encoding, UID uniqueness, cycle rejection, invalid ownership and refusal to silently drop legacy links.
- The CI workflow includes PHP 8.1–8.4 and integration checks against Kanboard 1.2.50 and 1.2.57.
- Some tests use synthetic fixture dates rather than live project database data; both are relevant and must not be conflated.

## Known limitations and release gates

1. **Calendar/duration presentation** varies across ProjectLibre and Open Planner Studio even when the XML contains identical original timestamps. The export uses 24x7 metadata; a planner may recalculate or display duration in its own convention.
2. **Full destination interoperability** is not certified: ProjectLibre dependency recognition was manually verified; Open Planner Studio and OrkFlow Gantt Kanboard dependency rendering and CPM policies require independent checks.
3. **Summary-card endpoints** map to original-period auxiliary rows. A card with no suitable planned start may cause explicit export failure; this is preferable to silently dropping a dependency.
4. **Legacy simplified export** is unsuitable when native-subtask dependencies are present.
5. **Cycles and identities** are checked at registration/export, but time feasibility and resource leveling are intentionally left to scheduling tools.
6. Before a stable release, run regression tests on the actual packaged candidate, confirm current branch CI, verify clean installation/migration and restore procedure, inspect any reviewer feedback, and publish a changelog and compatible version metadata.

## Design boundary

Kanboard stores and tracks source activities; Schedule Exchange validates and exports them; specialized applications compute and visualize schedules. **No automatic rescheduling or change of Kanboard source dates is in scope.**
