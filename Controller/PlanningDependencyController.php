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

    /**
     * Parse a selector representing a native card or one of its subtasks.
     * Ownership and project validation are enforced by PlanningDependencyModel.
     */
    private function parseActivity($value)
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Invalid activity selection');
        }
        if (preg_match('/^task:([1-9][0-9]*)$/D', $value, $m)) {
            return array(PlanningDependencyModel::ENTITY_TASK, (int)$m[1], null);
        }
        if (preg_match('/^subtask:([1-9][0-9]*):([1-9][0-9]*)$/D', $value, $m)) {
            return array(PlanningDependencyModel::ENTITY_SUBTASK, (int)$m[1], (int)$m[2]);
        }
        throw new \InvalidArgumentException('Invalid activity selection');
    }

    public function create()
    {
        $this->checkCSRFForm();
        $task = $this->getTask();
        $projectId = (int) $task['project_id'];
        $values = $this->request->getValues();
        $predecessor = (string)($values['predecessor_activity'] ?? '');
        $successor = (string)($values['successor_activity'] ?? '');
        $relationType = strtoupper(trim((string)($values['relation_type'] ?? 'FS')));
        $lagDays = (int)($values['lag_days'] ?? 0);

        try {
            list($fromType, $fromTaskId, $fromSubtaskId) = $this->parseActivity($predecessor);
            list($toType, $toTaskId, $toSubtaskId) = $this->parseActivity($successor);
            if ($toTaskId !== (int)$task['id']) {
                throw new \InvalidArgumentException('Successor must belong to current card');
            }
            $this->model()->create(
                $projectId,
                $fromType,
                $fromTaskId,
                $fromSubtaskId,
                $toType,
                $toTaskId,
                $toSubtaskId,
                $relationType,
                $lagDays,
                (int)$this->userSession->getId()
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
        $matching = array_filter($this->model()->getByProject($projectId), function ($dependency) use ($task) {
            return (int)$dependency['to_task_id'] === (int)$task['id'];
        });
        $owned = false;
        foreach ($matching as $dependency) {
            if ((int)$dependency['id'] === (int)$dependencyId) {
                $owned = true;
                break;
            }
        }
        if (!$owned) {
            $this->flash->failure(t('Unable to remove this planning predecessor.'));
        } elseif ($this->model()->delete($projectId, $dependencyId)) {
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
