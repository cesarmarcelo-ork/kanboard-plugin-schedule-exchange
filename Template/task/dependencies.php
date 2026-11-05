<?php
$taskId = (int) $task['id'];
$projectId = (int) $task['project_id'];
$incoming = array_values(array_filter($planning_dependencies ?? array(), function ($dep) use ($taskId) {
    return $dep['to_entity_type'] === 'task' && (int)$dep['to_task_id'] === $taskId;
}));
?>
<section class="panel" style="margin-top:12px">
    <h3><?= t('Planning dependencies') ?></h3>

    <?php if (empty($incoming)): ?>
        <p><?= t('No predecessor registered.') ?></p>
    <?php else: ?>
        <ul>
        <?php foreach ($incoming as $dep): ?>
            <li>
                <?= $this->text->e('#' . (int)$dep['from_task_id'] . ' ' . ($planning_task_titles[(int)$dep['from_task_id']] ?? '')) ?>
                — <?= $this->text->e($dep['relation_type']) ?> · <?= t('Lag: %d day(s)', (int)$dep['lag_days']) ?>
                <form method="post" action="<?= $this->url->href('PlanningDependencyController', 'remove', array(
                    'plugin' => 'ScheduleExchange',
                    'project_id' => $projectId,
                    'task_id' => $taskId,
                    'dependency_id' => (int)$dep['id'],
                )) ?>" style="display:inline">
                    <?= $this->form->csrf() ?>
                    <button type="submit" class="btn btn-red btn-small"><?= t('Remove') ?></button>
                </form>
            </li>
        <?php endforeach ?>
        </ul>
    <?php endif ?>

    <form method="post" action="<?= $this->url->href('PlanningDependencyController', 'create', array(
        'plugin' => 'ScheduleExchange',
        'project_id' => $projectId,
        'task_id' => $taskId,
    )) ?>">
        <?= $this->form->csrf() ?>
        <label for="predecessor_task_id"><strong><?= t('Add predecessor') ?></strong></label>
        <select id="predecessor_task_id" name="predecessor_task_id" required>
            <option value=""><?= t('Select a task') ?></option>
            <?php foreach (($planning_tasks ?? array()) as $candidate): ?>
                <?php if ((int)$candidate['id'] !== $taskId): ?>
                    <option value="<?= (int)$candidate['id'] ?>">
                        <?= $this->text->e('#' . (int)$candidate['id'] . ' ' . $candidate['title']) ?>
                    </option>
                <?php endif ?>
            <?php endforeach ?>
        </select>
        <span style="margin-left:8px"><?= t('FS · Lag 0') ?></span>
        <button type="submit" class="btn btn-blue"><?= t('Add') ?></button>
    </form>
</section>
