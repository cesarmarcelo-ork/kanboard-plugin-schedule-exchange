<?php if ($this->user->hasProjectAccess('ScheduleExchangeController', 'download', $project['id'])): ?>
    <li>
        <?= $this->url->icon(
            'download',
            t('Export project schedule (.xml)'),
            'ScheduleExchangeController',
            'download',
            array('project_id' => $project['id'], 'plugin' => 'ScheduleExchange')
        ) ?>
    </li>
<?php endif ?>
