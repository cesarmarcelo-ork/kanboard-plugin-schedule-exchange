<?php
$taskId = (int)$task['id'];
$projectId = (int)$task['project_id'];
$subs = $planning_subtasks_by_task ?? array();
$taskTitles = $planning_task_titles ?? array();
$subTitles = $planning_subtask_titles ?? array();
$incoming = array_values(array_filter($planning_dependencies ?? array(), function ($dep) use ($taskId) {
    return (int)$dep['to_task_id'] === $taskId;
}));

$activityLabel = function ($type, $cardId, $subId = null) use ($taskTitles, $subTitles) {
    $card = '#'.(int)$cardId.' '.($taskTitles[(int)$cardId] ?? '');
    return $type === 'subtask'
        ? $card.' / '.t('Subtask').' #'.(int)$subId.' '.($subTitles[(int)$subId] ?? '')
        : $card;
};
?>
<style>
.schedule-deps{margin-top:12px}
.schedule-deps__intro{margin:10px 0;color:var(--color-text-light,#666)}
.schedule-deps__list{display:grid;gap:8px;margin:12px 0}
.schedule-deps__item{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px;border:1px solid #d7dfe9;border-radius:5px}
.schedule-deps__main{min-width:0;overflow-wrap:anywhere}
.schedule-deps__meta{font-size:12px;color:var(--color-text-light,#666);margin-top:4px}
.schedule-deps__form{border-top:1px solid #d7dfe9;margin-top:12px;padding-top:14px}
.schedule-deps__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:12px 0}
.schedule-deps__field label{display:block;font-weight:600;margin-bottom:6px}
.schedule-deps__field select,.schedule-deps__field input{width:100%;box-sizing:border-box;min-height:36px}
.schedule-deps__actions{display:flex;align-items:center;flex-wrap:wrap;gap:12px}
.schedule-deps__empty{padding:10px;border-left:3px solid #4379a8;background:rgba(67,121,168,.08)}
.schedule-deps__hint{font-size:12px;color:var(--color-text-light,#666);margin-top:12px}
@media(max-width:700px){.schedule-deps__grid{grid-template-columns:1fr}.schedule-deps__item{flex-direction:column;align-items:flex-start}}
</style>
<details class="panel schedule-deps" id="schedule-dependencies">
    <summary style="cursor:pointer;font-weight:600;font-size:1.1em;padding:4px 0"><?= t('Planning dependencies') ?></summary>
    <p class="schedule-deps__intro"><?= t('Define the predecessor and successor activities, their relationship, and lag or lead time.') ?></p>
    <?php if (empty($incoming)): ?>
        <div class="schedule-deps__empty"><?= t('No predecessor registered.') ?></div>
    <?php else: ?>
        <div class="schedule-deps__list">
        <?php foreach ($incoming as $dep): ?>
            <div class="schedule-deps__item">
                <div class="schedule-deps__main">
                    <strong><?= $this->text->e($activityLabel($dep['from_entity_type'], $dep['from_task_id'], $dep['from_subtask_id'])) ?></strong>
                    &rarr;
                    <strong><?= $this->text->e($activityLabel($dep['to_entity_type'], $dep['to_task_id'], $dep['to_subtask_id'])) ?></strong>
                    <div class="schedule-deps__meta">
                        <?= $this->text->e((string)$dep['relation_type']) ?> -
                        <?= t('Lag: %d day(s)', (int)$dep['lag_days']) ?>
                    </div>
                </div>
                <form method="post" action="<?= $this->url->href('PlanningDependencyController', 'remove', array(
                    'plugin'=>'ScheduleExchange','project_id'=>$projectId,
                    'task_id'=>$taskId,'dependency_id'=>(int)$dep['id']
                )) ?>">
                    <?= $this->form->csrf() ?>
                    <button type="submit" class="btn btn-red btn-small"
                        onclick="return confirm('<?= $this->text->e(t('Remove this planning predecessor?')) ?>')"><?= t('Remove') ?></button>
                </form>
            </div>
        <?php endforeach ?>
        </div>
    <?php endif ?>
    <?php if ($this->user->hasProjectAccess('PlanningDependencyController', 'create', $projectId)): ?>
    <div class="schedule-deps__form">
        <strong><?= t('Add a new predecessor') ?></strong>
        <form method="post" action="<?= $this->url->href('PlanningDependencyController','create',array(
            'plugin'=>'ScheduleExchange','project_id'=>$projectId,'task_id'=>$taskId
        )) ?>">
            <?= $this->form->csrf() ?>
            <div class="schedule-deps__grid">
                <div class="schedule-deps__field">
                    <label for="predecessor_activity"><?= t('Predecessor activity') ?></label>
                    <select name="predecessor_activity" id="predecessor_activity" required>
                        <option value=""><?= t('Select the predecessor activity') ?></option>
                        <?php foreach ($planning_tasks ?? array() as $candidate): ?>
                            <?php $id=(int)$candidate['id']; ?>
                            <optgroup label="<?= $this->text->e('#'.$id.' '.$candidate['title']) ?>">
                                <option value="task:<?= $id ?>"><?= $this->text->e(t('Task').' #'.$id.' - '.$candidate['title']) ?></option>
                                <?php foreach ($subs[$id] ?? array() as $sub): ?>
                                    <option value="subtask:<?= $id ?>:<?= (int)$sub['id'] ?>"><?= $this->text->e(t('Subtask').' #'.(int)$sub['id'].' - '.$sub['title']) ?></option>
                                <?php endforeach ?>
                            </optgroup>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="schedule-deps__field">
                    <label for="successor_activity"><?= t('Successor activity') ?></label>
                    <select name="successor_activity" id="successor_activity" required>
                        <option value="task:<?= $taskId ?>"><?= $this->text->e(t('Task').' #'.$taskId.' - '.$task['title']) ?></option>
                        <?php foreach ($subs[$taskId] ?? array() as $sub): ?>
                            <option value="subtask:<?= $taskId ?>:<?= (int)$sub['id'] ?>"><?= $this->text->e(t('Subtask').' #'.(int)$sub['id'].' - '.$sub['title']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="schedule-deps__field">
                    <label for="relation_type"><?= t('Dependency relationship') ?></label>
                    <select name="relation_type" id="relation_type">
                        <option value="FS"><?= t('FS - Finish to Start') ?></option>
                        <option value="SS"><?= t('SS - Start to Start') ?></option>
                        <option value="FF"><?= t('FF - Finish to Finish') ?></option>
                        <option value="SF"><?= t('SF - Start to Finish') ?></option>
                    </select>
                </div>
                <div class="schedule-deps__field">
                    <label for="lag_days"><?= t('Lag (days)') ?></label>
                    <input type="number" name="lag_days" id="lag_days" value="0" min="-3650" max="3650" step="1" required>
                </div>
            </div>
            <div class="schedule-deps__actions">
                <button type="submit" class="btn btn-blue"><?= t('Add predecessor') ?></button>
            </div>
            <p class="schedule-deps__hint"><?= t('Choose a card or a subtask on each side. Positive lag delays the successor; negative lag advances it.') ?></p>
        </form>
    </div>
    <?php endif ?>
</details>
