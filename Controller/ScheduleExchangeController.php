<?php

namespace Kanboard\Plugin\ScheduleExchange\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\ScheduleExchange\Exporter\ProjectXmlExporter;
use Kanboard\Plugin\ScheduleExchange\Model\SubtaskPlanModel;
use Kanboard\Plugin\ScheduleExchange\Model\SubtaskCompletionModel;
use Kanboard\Plugin\ScheduleExchange\Model\PlanningDependencyModel;

/** Exports only the project the authenticated Kanboard user may view. */
class ScheduleExchangeController extends BaseController
{
    public function download()
    {
        $project = $this->getProject();
        $projectId = (int) $project['id'];

        // No credentials, API tokens, external services, DB writes or OS commands.
        $open = $this->taskFinderModel->getAll($projectId, 1);
        $closed = $this->taskFinderModel->getAll($projectId, 0);
        $tasks = array_merge($open, $closed);

        $knownIds = array();
        foreach ($tasks as $task) {
            $knownIds[(int) $task['id']] = true;
        }
        $links = array();
        foreach (array_keys($knownIds) as $taskId) {
            $links[$taskId] = $this->taskLinkModel->getAll($taskId);
        }

        $subtasksByTask = array();
        // One read-only query for all native Kanboard subtasks.
        foreach ($this->subtaskModel->getAllByTaskIds(array_keys($knownIds)) as $subtask) {
            $subtasksByTask[(int) $subtask['task_id']][] = $subtask;
        }

        $exporter = new ProjectXmlExporter($this->timezoneModel->getCurrentTimezone());
        // Detailed export is the default. Legacy format remains available via ?subtasks=legacy.
        $detailed = $this->request->getStringParam('subtasks') !== 'legacy';
        $plans = $detailed ? (new SubtaskPlanModel($this->db->getConnection()))->getByTaskIds(array_keys($knownIds)) : array();
        $completions = $detailed ? (new SubtaskCompletionModel($this->db->getConnection()))->getByTaskIds(array_keys($knownIds)) : array();
        $planningDependencies = (new PlanningDependencyModel($this->db->getConnection()))->getByProject($projectId);
        $xml = $exporter->generate($project, $tasks, $links, null, $subtasksByTask, $detailed, $plans, $completions, $planningDependencies);
        $filename = 'kanboard-' . $projectId . ($detailed ? '-subtarefas' : '') . '-cronograma.xml';
        $this->response->withFileDownload($filename);
        $this->response->xml($xml);
    }
}
