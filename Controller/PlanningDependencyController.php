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
        $relationType = strtoupper(trim((string)($values['relation_type'] ?? 'FS')));
        $lagDays = (int)($values['lag_days'] ?? 0);

        try {
            $this->model()->create(
                $projectId,
                PlanningDependencyModel::ENTITY_TASK,
                $predecessorId,
                null,
                PlanningDependencyModel::ENTITY_TASK,
                (int) $task['id'],
                null,
                $relationType,
                $lagDays,
                (int) $this->userSession->getId()
            );
            $this->flash->success(t('Planning predecessor added successfully.'));
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            if ($message === 'Dependency already exists') {
                $this->flash->failure(t('This predecessor is already registered for this activity.'));
            } elseif ($message === 'An activity cannot depend on itself') {
                $this->flash->failure(t('An activity cannot be its own predecessor.'));
            } elseif ($message === 'Invalid dependency relation type') {
                $this->flash->failure(t('Select a valid dependency relationship.'));
            } elseif ($message === 'Dependency lag out of range') {
                $this->flash->failure(t('Enter a lag between -3650 and 3650 days.'));
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
