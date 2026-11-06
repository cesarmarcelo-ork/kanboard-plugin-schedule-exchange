<?php

namespace Kanboard\Plugin\ScheduleExchange;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Core\Translator;

/**
 * Schedule planning, dependency management and interoperable schedule exchange.
 */
class Plugin extends Base
{
    public function initialize()
    {
        $this->container['scheduleDependencyModel'] = function ($container) {
            return new \Kanboard\Plugin\ScheduleExchange\Model\PlanningDependencyModel($container['db']->getConnection());
        };
        $this->container['subtaskModel'] = function ($container) {
            return new \Kanboard\Plugin\ScheduleExchange\Model\CompletionSubtaskModel($container);
        };
        $this->container['subtaskStatusModel'] = function ($container) {
            return new \Kanboard\Plugin\ScheduleExchange\Model\CompletionSubtaskStatusModel($container);
        };
        $this->projectAccessMap->add('SubtaskCompletionController', 'edit', Role::PROJECT_VIEWER);
        $this->projectAccessMap->add('SubtaskCompletionController', 'save', Role::PROJECT_MEMBER);
        // Replace only the native conversion service, preserving its public method contract.
        $this->container['subtaskTaskConversionModel'] = function ($container) {
            return new \Kanboard\Plugin\ScheduleExchange\Model\PlannedSubtaskConversionModel($container);
        };

        $this->projectAccessMap->add('ScheduleExchangeController', 'download', Role::PROJECT_VIEWER);
        $this->projectAccessMap->add('PlanningDependencyController', 'create', Role::PROJECT_MEMBER);
        $this->projectAccessMap->add('PlanningDependencyController', 'remove', Role::PROJECT_MEMBER);
        $this->projectAccessMap->add('SubtaskPlanController', 'edit', Role::PROJECT_VIEWER);
        $this->projectAccessMap->add('SubtaskPlanController', 'save', Role::PROJECT_MEMBER);
        $this->projectAccessMap->add('SubtaskPlanController', 'remove', Role::PROJECT_MEMBER);
        $this->projectAccessMap->add('PlannedSubtaskCreationController', 'create', Role::PROJECT_MEMBER);
        $this->projectAccessMap->add('PlannedSubtaskCreationController', 'save', Role::PROJECT_MEMBER);
        $this->projectAccessMap->add('TaskPlanController', 'edit', Role::PROJECT_MEMBER);
        $this->projectAccessMap->add('TaskPlanController', 'save', Role::PROJECT_MEMBER);
        $this->template->hook->attach(
            'template:subtask:table:header:before-timetracking',
            'ScheduleExchange:subtask/period-header'
        );
        $this->template->hook->attachCallable(
            'template:subtask:table:rows',
            'ScheduleExchange:subtask/period-link',
            function ($subtask) {
                $projectId = $this->subtaskModel->getProjectId((int)$subtask['id']);
                $plan = null;
                if ($projectId && $this->helper->user->hasProjectAccess('SubtaskPlanController', 'edit', $projectId)) {
                    $model = new \Kanboard\Plugin\ScheduleExchange\Model\SubtaskPlanModel($this->db->getConnection());
                    $plan = $model->get((int)$subtask['id']);
                }
                return array(
                    'schedule_export_project_id' => $projectId,
                    'schedule_export_plan' => $plan,
                );
            }
        );
        $this->template->hook->attach('template:subtask:table:header:before-timetracking', 'ScheduleExchange:subtask/completion-header');
        $this->template->hook->attachCallable('template:subtask:table:rows', 'ScheduleExchange:subtask/completion-link', function ($subtask) {
            $projectId = $this->subtaskModel->getProjectId((int)$subtask['id']);
            $completion = null;
            if ($projectId && $this->helper->user->hasProjectAccess('SubtaskCompletionController', 'edit', $projectId)) {
                $model = new \Kanboard\Plugin\ScheduleExchange\Model\SubtaskCompletionModel($this->db->getConnection());
                $completion = $model->get((int)$subtask['id']);
            }
            return array('completion_project_id' => $projectId, 'completion' => $completion);
        });
        $this->template->hook->attach(
            'template:subtask:form:create',
            'ScheduleExchange:subtask/create-entry'
        );
        $this->template->hook->attach(
            'template:project:dropdown',
            'ScheduleExchange:project/dropdown'
        );
        // Task sidebar navigation, without overriding native task actions.
        $this->template->hook->attach(
            'template:task:sidebar:after-add-comment',
            'ScheduleExchange:task/sidebar-actions'
        );

        $this->template->hook->attachCallable(
            'template:task:show:top',
            'ScheduleExchange:task/planning',
            function ($task, $project) {
                return array(
                    'subtasks' => $this->subtaskModel->getAll((int) $task['id']),
                );
            }
        );

        $this->template->hook->attachCallable(
            'template:task:show:top',
            'ScheduleExchange:task/dependencies',
            function ($task, $project) {
                $projectId = (int) $project['id'];
                $open = $this->taskFinderModel->getAll($projectId, 1);
                $closed = $this->taskFinderModel->getAll($projectId, 0);
                $tasks = array_merge($open, $closed);
                $titles = array();
                foreach ($tasks as $candidate) {
                    $titles[(int)$candidate['id']] = (string)$candidate['title'];
                }

                return array(
                    'planning_dependencies' => $this->scheduleDependencyModel->getByProject($projectId),
                    'planning_tasks' => $tasks,
                    'planning_task_titles' => $titles,
                );
            }
        );
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__ . '/Locale');
    }

    public function getPluginName()
    {
        return 'Kanboard Schedule Exchange';
    }

    public function getPluginDescription()
    {
        return 'Plan, exchange and synchronize Kanboard schedules with interoperable planning applications';
    }

    public function getPluginAuthor()
    {
        return 'OrkFlowTech';
    }

    public function getPluginVersion()
    {
        return '0.1.0-dev';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/cesarmarcelo-ork/kanboard-plugin-schedule-exchange';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.50';
    }
}
