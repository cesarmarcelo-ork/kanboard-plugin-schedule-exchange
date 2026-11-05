<?php
namespace Kanboard\Plugin\ScheduleExchange\Schema;
use PDO;
const VERSION = 3;
function version_1(PDO $pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedule_export_subtask_plans (
        subtask_id INT NOT NULL,
        task_id INT NOT NULL,
        project_id INT NOT NULL,
        planned_start VARCHAR(19) DEFAULT NULL,
        planned_end VARCHAR(19) DEFAULT NULL,
        workday_minutes INT NOT NULL DEFAULT 480,
        workdays VARCHAR(30) NOT NULL DEFAULT '1,2,3,4,5',
        updated_at BIGINT NOT NULL,
        updated_by INT NOT NULL,
        PRIMARY KEY(subtask_id),
        FOREIGN KEY(subtask_id) REFERENCES subtasks(id) ON DELETE CASCADE,
        FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function version_2(PDO $pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedule_export_subtask_completions (
        subtask_id INT PRIMARY KEY,
        completed_at BIGINT NULL,
        revision INT NOT NULL DEFAULT 0,
        FOREIGN KEY(subtask_id) REFERENCES subtasks(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedule_export_completion_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subtask_id INT NOT NULL,
        previous_completed_at BIGINT NULL,
        completed_at BIGINT NULL,
        action VARCHAR(20) NOT NULL,
        changed_at BIGINT NOT NULL,
        changed_by INT NOT NULL,
        INDEX schedule_completion_history_subtask (subtask_id),
        FOREIGN KEY(subtask_id) REFERENCES subtasks(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function version_3(PDO $pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedule_exchange_dependencies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        from_entity_type VARCHAR(10) NOT NULL,
        from_task_id INT NOT NULL,
        from_subtask_id INT NULL,
        to_entity_type VARCHAR(10) NOT NULL,
        to_task_id INT NOT NULL,
        to_subtask_id INT NULL,
        relation_type VARCHAR(2) NOT NULL DEFAULT 'FS',
        lag_days INT NOT NULL DEFAULT 0,
        created_at BIGINT NOT NULL,
        created_by INT NOT NULL,
        updated_at BIGINT NOT NULL,
        updated_by INT NOT NULL,
        INDEX schedule_exchange_dependencies_project (project_id),
        INDEX schedule_exchange_dependencies_from (from_task_id, from_subtask_id),
        INDEX schedule_exchange_dependencies_to (to_task_id, to_subtask_id),
        FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY(from_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY(from_subtask_id) REFERENCES subtasks(id) ON DELETE CASCADE,
        FOREIGN KEY(to_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY(to_subtask_id) REFERENCES subtasks(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
