<?php

namespace Kanboard\Plugin\ScheduleExchange\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\ScheduleExchange\Exporter\ProjectXmlExporter;
use Kanboard\Plugin\ScheduleExchange\Exporter\ExportDateValidationException;
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
        try {
            $xml = $exporter->generate($project, $tasks, $links, null, $subtasksByTask, $detailed, $plans, $completions, $planningDependencies);
        } catch (ExportDateValidationException $e) {
            // A failed export returns actionable HTML, never a partial XML file.
            $items = '';
            foreach ($e->getIssues() as $issue) {
                $items .= '<li>' . htmlspecialchars($issue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
            }
            $this->response->html(
                '<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8">' .
                '<title>Datas inconsistentes - Kanboard Schedule Exchange</title></head><body>' .
                '<main><h1>Exportação XML interrompida: datas inconsistentes</h1>' .
                '<p>Corrija os registros abaixo no Kanboard e tente exportar novamente. Nenhum arquivo XML foi gerado.</p>' .
                '<ul>' . $items . '</ul></main></body></html>',
                422
            );
            return;
        } catch (\InvalidArgumentException $e) {
            // Other source validation errors are also user errors, not internal server errors.
            $this->response->html(
                '<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8">' .
                '<title>Exportação não realizada</title></head><body>' .
                '<h1>Exportação XML não realizada</h1>' .
                '<p>O projeto contém dados de planejamento inválidos. Revise os cartões, subtarefas e dependências antes de tentar novamente.</p>' .
                '</body></html>',
                422
            );
            return;
        }
        $filename = 'kanboard-' . $projectId . ($detailed ? '-subtarefas' : '') . '-cronograma.xml';
        $this->response->withFileDownload($filename);
        $this->response->xml($xml);
    }
}
