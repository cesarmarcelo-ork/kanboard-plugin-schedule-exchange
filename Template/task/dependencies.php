<?php
$taskId = (int) $task['id'];
$projectId = (int) $task['project_id'];
$incoming = array_values(array_filter($planning_dependencies ?? array(), function ($dep) use ($taskId) {
    return $dep['to_entity_type'] === 'task' && (int)$dep['to_task_id'] === $taskId;
}));

$existingPredecessorIds = array();
foreach ($incoming as $dep) {
    $existingPredecessorIds[(int)$dep['from_task_id']] = true;
}

$availableTasks = array_values(array_filter($planning_tasks ?? array(), function ($candidate) use ($taskId, $existingPredecessorIds) {
    $candidateId = (int)$candidate['id'];
    return $candidateId !== $taskId && !isset($existingPredecessorIds[$candidateId]);
}));
?>
<style>
.schedule-deps{margin-top:12px}
.schedule-deps__intro{margin:0 0 14px;color:var(--color-text-light,#666)}
.schedule-deps__list{display:grid;gap:8px;margin:0 0 16px}
.schedule-deps__item{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid #d7dfe9;border-radius:6px;background:var(--color-background,#fff)}
.schedule-deps__main{min-width:0}
.schedule-deps__title{font-weight:600}
.schedule-deps__meta{margin-top:3px;font-size:12px;color:var(--color-text-light,#666)}
.schedule-deps__flow{display:inline-block;margin:0 6px;font-weight:700}
.schedule-deps__form{border-top:1px solid #d7dfe9;padding-top:14px}
.schedule-deps__step{font-weight:600;margin-bottom:6px}
.schedule-deps__select{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.schedule-deps__select select{min-width:360px;max-width:100%}
.schedule-deps__hint{margin-top:8px;font-size:12px;color:var(--color-text-light,#666)}
.schedule-deps__empty{padding:10px 12px;background:rgba(67,121,168,.08);border-left:3px solid #4379a8;margin-bottom:14px}
@media(max-width:700px){.schedule-deps__item{align-items:flex-start;flex-direction:column}.schedule-deps__select select{min-width:100%;width:100%}}
</style>

<section class="panel schedule-deps">
    <h3><?= t('Planning dependencies') ?></h3>
    <p class="schedule-deps__intro">
        <?= t('Define which activity must finish before this card can start. For this first test, the relationship is FS with zero lag.') ?>
    </p>

    <?php if (empty($incoming)): ?>
        <div class="schedule-deps__empty">
            <strong><?= t('No predecessor registered.') ?></strong><br>
            <?= t('This activity can currently start without a planning dependency.') ?>
        </div>
    <?php else: ?>
        <div class="schedule-deps__list">
        <?php foreach ($incoming as $dep): ?>
            <?php
            $predId = (int)$dep['from_task_id'];
            $predTitle = $planning_task_titles[$predId] ?? '';
            ?>
            <div class="schedule-deps__item">
                <div class="schedule-deps__main">
                    <div class="schedule-deps__title">
                        <?= $this->text->e('#'.$predId.' '.$predTitle) ?>
                        <span class="schedule-deps__flow">→</span>
                        <?= $this->text->e('#'.$taskId.' '.$task['title']) ?>
                    </div>
                    <div class="schedule-deps__meta">
                        <?= t('Relationship: %s', $dep['relation_type']) ?> ·
                        <?= t('Lag: %d day(s)', (int)$dep['lag_days']) ?>
                    </div>
                </div>
                <form method="post" action="<?= $this->url->href('PlanningDependencyController', 'remove', array(
                    'plugin' => 'ScheduleExchange',
                    'project_id' => $projectId,
                    'task_id' => $taskId,
                    'dependency_id' => (int)$dep['id'],
                )) ?>">
                    <?= $this->form->csrf() ?>
                    <button type="submit" class="btn btn-red btn-small"
                        onclick="return confirm('<?= $this->text->e(t('Remove this planning predecessor?')) ?>')">
                        <?= t('Remove') ?>
                    </button>
                </form>
            </div>
        <?php endforeach ?>
        </div>
    <?php endif ?>

    <div class="schedule-deps__form">
        <div class="schedule-deps__step"><?= t('Add a new predecessor') ?></div>

        <?php if (empty($availableTasks)): ?>
            <p><?= t('There are no other available cards to add as predecessors.') ?></p>
        <?php else: ?>
            <form method="post" action="<?= $this->url->href('PlanningDependencyController', 'create', array(
                'plugin' => 'ScheduleExchange',
                'project_id' => $projectId,
                'task_id' => $taskId,
            )) ?>">
                <?= $this->form->csrf() ?>
                <div class="schedule-deps__select">
                    <select id="predecessor_task_id" name="predecessor_task_id" required>
                        <option value=""><?= t('Select the activity that must finish first') ?></option>
                        <?php foreach ($availableTasks as $candidate): ?>
                            <option value="<?= (int)$candidate['id'] ?>">
                                <?= $this->text->e('#'.(int)$candidate['id'].' '.$candidate['title']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <span><strong><?= t('FS') ?></strong> · <?= t('Lag: 0 days') ?></span>
                    <button type="submit" class="btn btn-blue"><?= t('Add predecessor') ?></button>
                </div>
                <div class="schedule-deps__hint">
                    <?= t('Example: if Activity A is the predecessor of Activity B, B starts only after A finishes.') ?>
                </div>
            </form>
        <?php endif ?>
    </div>
</section>
