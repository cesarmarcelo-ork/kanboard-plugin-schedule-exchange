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

        try {
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
            $this->flash->success(t('Planning predecessor added successfully.'));
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            if ($message === 'Dependency already exists') {
                $this->flash->failure(t('This predecessor is already registered for this activity.'));
            } elseif ($message === 'An activity cannot depend on itself') {
                $this->flash->failure(t('An activity cannot be its own predecessor.'));
            } else {
                $this->flash->failure(t('Unable to add this planning predecessor.'));
            }
        }

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
        if ($this->model()->delete($projectId, $dependencyId)) {
            $this->flash->success(t('Planning predecessor removed.'));
        } else {
            $this->flash->failure(t('Unable to remove this planning predecessor.'));
        }

        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array(
            'task_id' => $task['id'],
            'project_id' => $projectId,
        )));
    }
}
