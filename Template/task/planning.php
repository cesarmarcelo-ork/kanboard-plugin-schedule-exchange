<?php
// This hook is rendered on a task page with $task and (in native Kanboard) $subtasks.
if (isset($task) && !empty($task['id']) && isset($subtasks) && is_array($subtasks)):
?>
<details class="panel schedule-exchange-collapse" id="schedule-subtasks" open>
    <summary style="cursor:pointer;font-weight:600;font-size:1.1em;padding:4px 0"><?= t('Subtask planning') ?></summary>
    <p><?= t('Dates are stored in Kanboard. No dates means an unscheduled task, zero duration, and no milestone.') ?></p>
    <ul>
    <?php foreach ($subtasks as $sub): ?>
    <li><?= $this->text->e($sub['title']) ?>
        <?php if ($this->user->hasProjectAccess('SubtaskPlanController','edit',$task['project_id'])): ?>
        <?= $this->url->link(t('Dates'), 'SubtaskPlanController','edit',array('plugin'=>'ScheduleExchange','project_id'=>$task['project_id'],'task_id'=>$task['id'],'subtask_id'=>$sub['id'])) ?>
        <?php endif ?>
    </li>
    <?php endforeach ?>
    </ul>
</details>
<?php endif ?>
