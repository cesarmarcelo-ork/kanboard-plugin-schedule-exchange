<?php
// Accessible from the task sidebar. Task dates remain native Kanboard fields.
$projectId = (int) ($task['project_id'] ?? 0);
$taskId = (int) ($task['id'] ?? 0);
?>
<?php if ($taskId > 0 && $projectId > 0): ?>
<li class="schedule-exchange-sidebar-heading" style="padding-top:10px;font-weight:600">
    <?= $this->text->e(t('Schedule Exchange')) ?>
</li>
<?php if ($this->user->hasProjectAccess('SubtaskPlanController', 'edit', $projectId)): ?>
<li>
    <a href="<?= $this->url->href('TaskViewController', 'show', array('task_id' => $taskId, 'project_id' => $projectId)) ?>#schedule-subtasks">
        <?= $this->text->e(t('Subtask planning')) ?>
    </a>
</li>
<?php endif ?>
<?php if ($this->user->hasProjectAccess('TaskModificationController', 'edit', $projectId)): ?>
<li>
    <?= $this->url->link(t('Task planning'), 'TaskModificationController', 'edit', array('task_id' => $taskId, 'project_id' => $projectId)) ?>
</li>
<?php endif ?>
<?php endif ?>
