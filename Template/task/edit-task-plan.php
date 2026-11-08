<?php
$taskId = (int)$task['id'];
$projectId = (int)$task['project_id'];
?>
<style>
.schedule-task-plan{max-width:860px;margin:14px 0 28px}
.schedule-task-plan__card{padding:20px;border:1px solid #d7dfe9;border-radius:6px;background:var(--color-background,#fff)}
.schedule-task-plan__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin:18px 0}
.schedule-task-plan__field label{display:block;font-weight:600;margin-bottom:7px}
.schedule-task-plan__field input{width:100%;min-height:40px;box-sizing:border-box;padding:7px}
.schedule-task-plan__field .alert{margin:8px 0}
.schedule-task-plan__actions{display:flex;gap:14px;align-items:center;margin-top:18px}
@media(max-width:650px){.schedule-task-plan__grid{grid-template-columns:1fr}.schedule-task-plan__card{padding:12px}}
</style>
<div class="page-header"><h2><?= t('Task planning') ?></h2></div>
<div class="schedule-task-plan">
    <p><strong><?= $this->text->e($task['title']) ?></strong></p>
    <div class="schedule-task-plan__card">
        <p><?= t('Define the estimated start and end dates of the task.') ?></p>
        <form method="post" action="<?= $this->url->href('TaskPlanController', 'save', array(
            'plugin' => 'ScheduleExchange', 'task_id' => $taskId, 'project_id' => $projectId
        )) ?>">
            <?= $this->form->csrf() ?>
            <div class="schedule-task-plan__grid">
                <div class="schedule-task-plan__field">
                    <label for="date_started"><?= t('Estimated start date') ?></label>
                    <input type="datetime-local" id="date_started" name="date_started" step="60"
                        value="<?= $this->text->e($values['date_started'] ?? '') ?>">
                    <?php if (!empty($errors['date_started'])): ?>
                        <p class="alert alert-error"><?= $this->text->e($errors['date_started']) ?></p>
                    <?php endif ?>
                </div>
                <div class="schedule-task-plan__field">
                    <label for="date_due"><?= t('Estimated end date') ?></label>
                    <input type="datetime-local" id="date_due" name="date_due" step="60"
                        value="<?= $this->text->e($values['date_due'] ?? '') ?>">
                    <?php if (!empty($errors['date_due'])): ?>
                        <p class="alert alert-error"><?= $this->text->e($errors['date_due']) ?></p>
                    <?php endif ?>
                </div>
            </div>
            <p class="form-help"><?= t('These dates update the native Kanboard task schedule. Leave a field empty to clear its date.') ?></p>
            <div class="schedule-task-plan__actions">
                <button type="submit" class="btn btn-blue"><?= t('Save planning') ?></button>
                <?= $this->url->link(t('Cancel'), 'TaskViewController', 'show', array('task_id' => $taskId, 'project_id' => $projectId)) ?>
            </div>
        </form>
    </div>
</div>
