# Planning Dependencies — Kanboard Schedule Exchange

## Purpose

The Schedule Exchange dependency model represents planning precedence between native Kanboard activities while preserving Kanboard IDs as the authoritative identity.

Supported endpoint combinations:

- task → task
- task → subtask
- subtask → task
- subtask → subtask

## Identity rules

A dependency never uses a title as an identity key.

Each endpoint stores:

- entity type: `task` or `subtask`
- native Kanboard task ID
- native Kanboard subtask ID when the endpoint is a subtask

For a subtask, both the parent task ID and the native subtask ID are mandatory.

## Relationship rules

The schema is prepared for:

- FS — Finish to Start
- SS — Start to Start
- FF — Finish to Finish
- SF — Start to Finish

The first user interface may default to FS with zero lag, but the data model already supports all four relationship types.

Lag is stored in integer calendar days in the initial development model. Calendar-aware lag semantics can evolve later without changing endpoint identity.

## Persistence

New Exchange-specific data is stored in `schedule_exchange_dependencies`.

The inherited `schedule_export_*` tables remain unchanged in the development branch so the cloned v0.6.1 scheduling and completion behavior can be validated before any data migration is attempted.

## Validation

The model rejects:

- endpoints outside the selected project
- subtask IDs that do not belong to the stated parent task
- task endpoints containing subtask IDs
- subtask endpoints without subtask IDs
- self-dependencies
- unsupported relationship types
- exact duplicate dependencies

## XML direction

The future MSPDI exchange layer will map each Kanboard activity to an XML UID while preserving native Kanboard IDs in Exchange metadata. Predecessor links will reference XML UIDs, but round-trip matching back to Kanboard must use the preserved native IDs.

## Current development status

Implemented in `0.1.0-dev`:

- database schema for SQLite, MySQL and PostgreSQL
- `PlanningDependencyModel`
- validation for the four endpoint combinations
- FS/SS/FF/SF relation storage
- lag storage

Not yet implemented:

- Kanboard dependency user interface
- cycle detection across the complete network
- XML export of Exchange dependencies
- XML import / dry-run reconciliation
- OrkFlow round-trip integration
