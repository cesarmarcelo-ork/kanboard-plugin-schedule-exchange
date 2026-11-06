<?php
require_once __DIR__.'/../Model/PlanningDependencyModel.php';

use Kanboard\Plugin\ScheduleExchange\Model\PlanningDependencyModel;

function verify($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE tasks (id INTEGER PRIMARY KEY, project_id INTEGER NOT NULL)');
$db->exec('CREATE TABLE subtasks (id INTEGER PRIMARY KEY, task_id INTEGER NOT NULL)');
$db->exec('CREATE TABLE schedule_exchange_dependencies (
    id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER NOT NULL,
    from_entity_type VARCHAR(10) NOT NULL, from_task_id INTEGER NOT NULL,
    from_subtask_id INTEGER, to_entity_type VARCHAR(10) NOT NULL,
    to_task_id INTEGER NOT NULL, to_subtask_id INTEGER,
    relation_type VARCHAR(2) NOT NULL, lag_days INTEGER NOT NULL,
    created_at INTEGER NOT NULL, created_by INTEGER NOT NULL,
    updated_at INTEGER NOT NULL, updated_by INTEGER NOT NULL
)');
$db->exec('INSERT INTO tasks (id, project_id) VALUES (100, 7), (101, 7), (200, 8)');
$db->exec('INSERT INTO subtasks (id, task_id) VALUES (1001, 100), (1011, 101), (2001, 200)');
$model = new PlanningDependencyModel($db);
$cases = array(
    array('task', 100, null, 'task', 101, null, 'FS'),
    array('task', 100, null, 'subtask', 101, 1011, 'SS'),
    array('subtask', 100, 1001, 'task', 101, null, 'FF'),
    array('subtask', 100, 1001, 'subtask', 101, 1011, 'SF'),
);
foreach ($cases as $index => $case) {
    $id = $model->create(7, $case[0], $case[1], $case[2],
        $case[3], $case[4], $case[5], $case[6], $index, 1);
    verify($id > 0, 'Unable to create dependency variant '.$index);
}
$all = $model->getByProject(7);
verify(count($all) === 4, 'Four dependency variants must be persisted');
foreach ($cases as $index => $case) {
    verify($all[$index]['from_entity_type'] === $case[0], 'Incorrect predecessor endpoint');
    verify($all[$index]['to_entity_type'] === $case[3], 'Incorrect successor endpoint');
    verify($all[$index]['relation_type'] === $case[6], 'Incorrect dependency type');
}
$invalid = array(
    array('task', 100, null, 'task', 100, null, 'FS'), // same node
    array('task', 200, null, 'task', 101, null, 'FS'), // different project
    array('subtask', 100, 2001, 'task', 101, null, 'FS'), // subtask of another card
    array('subtask', 100, 1001, 'subtask', 100, 1001, 'FS'), // same subtask
);
foreach ($invalid as $case) {
    try {
        $model->create(7, $case[0], $case[1], $case[2],
            $case[3], $case[4], $case[5], $case[6], 0, 1);
        throw new RuntimeException('Invalid dependency was accepted');
    } catch (InvalidArgumentException $e) {
        // Expected.
    }
}
try {
    $model->create(7, 'task', 100, null, 'task', 101, null, 'FS', 0, 1);
    throw new RuntimeException('Duplicate dependency accepted');
} catch (InvalidArgumentException $e) {
    // Expected.
}
try {
    $model->create(7, 'subtask', 101, 1011, 'subtask', 100, 1001, 'FS', 0, 1);
    throw new RuntimeException('Circular dependency accepted');
} catch (InvalidArgumentException $e) {
    verify($e->getMessage() === 'Circular planning dependency', 'Wrong cycle rejection reason');
}
verify($model->delete(7, (int)$all[0]['id']), 'Delete must succeed');
verify(count($model->getByProject(7)) === 3, 'Deletion must be persisted');
echo "PASS: four task/subtask dependency variants, ownership, duplicates, deletion\n";
