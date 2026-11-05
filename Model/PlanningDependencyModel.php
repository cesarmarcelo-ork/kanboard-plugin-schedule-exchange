<?php
namespace Kanboard\Plugin\ScheduleExchange\Model;

use InvalidArgumentException;
use PDO;

/**
 * Persistent planning dependencies between Kanboard tasks and native subtasks.
 *
 * Supported endpoint combinations:
 * - task -> task
 * - task -> subtask
 * - subtask -> task
 * - subtask -> subtask
 *
 * IDs are always native Kanboard IDs. Titles are never used as identity keys.
 */
class PlanningDependencyModel
{
    const ENTITY_TASK = 'task';
    const ENTITY_SUBTASK = 'subtask';

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByProject($projectId)
    {
        $q = $this->pdo->prepare(
            'SELECT * FROM schedule_exchange_dependencies WHERE project_id = ? ORDER BY id'
        );
        $q->execute(array((int) $projectId));
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(
        $projectId,
        $fromEntityType,
        $fromTaskId,
        $fromSubtaskId,
        $toEntityType,
        $toTaskId,
        $toSubtaskId,
        $relationType,
        $lagDays,
        $userId
    ) {
        $projectId = (int) $projectId;
        $userId = (int) $userId;
        if ($projectId < 1 || $userId < 1) {
            throw new InvalidArgumentException('Invalid project or user');
        }

        $from = $this->normalizeEndpoint($projectId, $fromEntityType, $fromTaskId, $fromSubtaskId);
        $to = $this->normalizeEndpoint($projectId, $toEntityType, $toTaskId, $toSubtaskId);

        if ($from === $to) {
            throw new InvalidArgumentException('An activity cannot depend on itself');
        }

        $relationType = strtoupper(trim((string) $relationType));
        if (!in_array($relationType, array('FS', 'SS', 'FF', 'SF'), true)) {
            throw new InvalidArgumentException('Invalid dependency relation type');
        }

        $lagDays = (int) $lagDays;
        if ($lagDays < -3650 || $lagDays > 3650) {
            throw new InvalidArgumentException('Dependency lag out of range');
        }
        $now = time();

        // One dependency per endpoint pair. Relation type and lag are properties
        // of that dependency, not a way to create duplicate parallel links.
        $duplicate = $this->pdo->prepare(
            'SELECT id FROM schedule_exchange_dependencies
             WHERE project_id = ?
               AND from_entity_type = ? AND from_task_id = ?
               AND ((from_subtask_id IS NULL AND ? IS NULL) OR from_subtask_id = ?)
               AND to_entity_type = ? AND to_task_id = ?
               AND ((to_subtask_id IS NULL AND ? IS NULL) OR to_subtask_id = ?)'
        );
        $duplicate->execute(array(
            $projectId,
            $from['type'], $from['task_id'], $from['subtask_id'], $from['subtask_id'],
            $to['type'], $to['task_id'], $to['subtask_id'], $to['subtask_id']
        ));
        if ($duplicate->fetchColumn()) {
            throw new InvalidArgumentException('Dependency already exists');
        }

        $q = $this->pdo->prepare(
            'INSERT INTO schedule_exchange_dependencies
             (project_id, from_entity_type, from_task_id, from_subtask_id,
              to_entity_type, to_task_id, to_subtask_id,
              relation_type, lag_days, created_at, created_by, updated_at, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $q->execute(array(
            $projectId,
            $from['type'], $from['task_id'], $from['subtask_id'],
            $to['type'], $to['task_id'], $to['subtask_id'],
            $relationType, $lagDays,
            $now, $userId, $now, $userId
        ));

        return (int) $this->pdo->lastInsertId();
    }

    public function delete($projectId, $dependencyId)
    {
        $q = $this->pdo->prepare(
            'DELETE FROM schedule_exchange_dependencies WHERE id = ? AND project_id = ?'
        );
        return $q->execute(array((int) $dependencyId, (int) $projectId));
    }

    private function normalizeEndpoint($projectId, $type, $taskId, $subtaskId)
    {
        $type = strtolower(trim((string) $type));
        $taskId = (int) $taskId;
        $subtaskId = $subtaskId === null || $subtaskId === '' ? null : (int) $subtaskId;

        if (!in_array($type, array(self::ENTITY_TASK, self::ENTITY_SUBTASK), true) || $taskId < 1) {
            throw new InvalidArgumentException('Invalid dependency endpoint');
        }

        $task = $this->pdo->prepare('SELECT id FROM tasks WHERE id = ? AND project_id = ?');
        $task->execute(array($taskId, (int) $projectId));
        if (!$task->fetchColumn()) {
            throw new InvalidArgumentException('Task does not belong to project');
        }

        if ($type === self::ENTITY_TASK) {
            if ($subtaskId !== null) {
                throw new InvalidArgumentException('Task endpoint cannot contain subtask ID');
            }
            return array('type' => $type, 'task_id' => $taskId, 'subtask_id' => null);
        }

        if ($subtaskId === null || $subtaskId < 1) {
            throw new InvalidArgumentException('Subtask endpoint requires subtask ID');
        }

        $subtask = $this->pdo->prepare('SELECT id FROM subtasks WHERE id = ? AND task_id = ?');
        $subtask->execute(array($subtaskId, $taskId));
        if (!$subtask->fetchColumn()) {
            throw new InvalidArgumentException('Subtask does not belong to task');
        }

        return array('type' => $type, 'task_id' => $taskId, 'subtask_id' => $subtaskId);
    }
}
