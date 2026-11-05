<?php
namespace Kanboard\Plugin\ScheduleExchange\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\ScheduleExchange\Model\PlanningDependencyModel;

class PlanningDependencyController extends BaseController
{
    private function model()
    {
        return new PlanningDependencyModel($this->db->getConnection());
    }

    public function create()
    {
        $this->checkCSRFForm();
        $task = $this->getTask();
        $projectId = (int) $task['project_id'];
        $values = $this->request->getValues();
        $predecessorId = (int) ($values['predecessor_task_id'] ?? 0);

        $this->model()->create(
            $projectId,
            PlanningDependencyModel::ENTITY_TASK,
            $predecessorId,
            null,
            PlanningDependencyModel::ENTITY_TASK,
            (int) $task['id'],
            null,
            'FS',
            0,
            (int) $this->userSession->getId()
        );

        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array(
            'task_id' => $task['id'],
            'project_id' => $projectId,
        )));
    }

    public function remove()
    {
        $this->checkCSRFForm();
        $task = $this->getTask();
        $projectId = (int) $task['project_id'];
        $dependencyId = $this->request->getIntegerParam('dependency_id');
        $this->model()->delete($projectId, $dependencyId);

        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array(
            'task_id' => $task['id'],
            'project_id' => $projectId,
        )));
    }
}
