<?php
namespace Kanboard\Plugin\ScheduleExchange\Schema;
use PDO;
const VERSION = 3;
function version_1(PDO $pdo)
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS schedule_export_subtask_plans (
        subtask_id INTEGER PRIMARY KEY,
        task_id INTEGER NOT NULL,
        project_id INTEGER NOT NULL,
        planned_start TEXT,
        planned_end TEXT,
        workday_minutes INTEGER NOT NULL DEFAULT 480,
        workdays TEXT NOT NULL DEFAULT "1,2,3,4,5",
        updated_at INTEGER NOT NULL,
        updated_by INTEGER NOT NULL,
        FOREIGN KEY(subtask_id) REFERENCES subtasks(id) ON DELETE CASCADE,
        FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE
    )');
}

function version_2(PDO $pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedule_export_subtask_completions (
        subtask_id INT PRIMARY KEY,
        completed_at BIGINT NULL,
        revision INT NOT NULL DEFAULT 0,
        FOREIGN KEY(subtask_id) REFERENCES subtasks(id) ON DELETE CASCADE
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedule_export_completion_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        subtask_id INT NOT NULL,
        previous_completed_at BIGINT NULL,
        completed_at BIGINT NULL,
        action VARCHAR(20) NOT NULL,
        changed_at BIGINT NOT NULL,
        changed_by INT NOT NULL,
        FOREIGN KEY(subtask_id) REFERENCES subtasks(id) ON DELETE CASCADE
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS schedule_completion_history_subtask ON schedule_export_completion_history (subtask_id)');
}

function version_3(PDO $pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedule_exchange_dependencies (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        from_entity_type VARCHAR(10) NOT NULL,
        from_task_id INTEGER NOT NULL,
        from_subtask_id INTEGER NULL,
        to_entity_type VARCHAR(10) NOT NULL,
        to_task_id INTEGER NOT NULL,
        to_subtask_id INTEGER NULL,
        relation_type VARCHAR(2) NOT NULL DEFAULT 'FS',
        lag_days INTEGER NOT NULL DEFAULT 0,
        created_at INTEGER NOT NULL,
        created_by INTEGER NOT NULL,
        updated_at INTEGER NOT NULL,
        updated_by INTEGER NOT NULL,
        FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY(from_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY(from_subtask_id) REFERENCES subtasks(id) ON DELETE CASCADE,
        FOREIGN KEY(to_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY(to_subtask_id) REFERENCES subtasks(id) ON DELETE CASCADE
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS schedule_exchange_dependencies_project ON schedule_exchange_dependencies (project_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS schedule_exchange_dependencies_from ON schedule_exchange_dependencies (from_task_id, from_subtask_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS schedule_exchange_dependencies_to ON schedule_exchange_dependencies (to_task_id, to_subtask_id)');
}
