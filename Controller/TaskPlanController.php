<?php

namespace Kanboard\Plugin\ScheduleExchange\Controller;

use DateTimeImmutable;
use Kanboard\Controller\BaseController;
use Kanboard\Core\Controller\AccessForbiddenException;

class TaskPlanController extends BaseController
{
    private function taskContext()
    {
        $task = $this->getTask();
        if (!$this->helper->projectRole->canUpdateTask($task)) {
            throw new AccessForbiddenException(t('You are not allowed to update tasks assigned to someone else.'));
        }
        return $task;
    }

    public function edit(array $values = array(), array $errors = array())
    {
        $task = $this->taskContext();
        if (empty($values)) {
            $values = array(
                'date_started' => !empty($task['date_started']) ? date('Y-m-d\TH:i', (int)$task['date_started']) : '',
                'date_due' => !empty($task['date_due']) ? date('Y-m-d\TH:i', (int)$task['date_due']) : '',
            );
        }

        $this->response->html($this->helper->layout->task('ScheduleExchange:task/edit-task-plan', array(
            'task' => $task,
            'project' => $this->projectModel->getById($task['project_id']),
            'values' => $values,
            'errors' => $errors,
        )));
    }

    public function save()
    {
        $this->checkCSRFForm();
        $task = $this->taskContext();
        $input = $this->request->getValues();
        $values = array();
        $errors = array();

        foreach (array('date_started', 'date_due') as $field) {
            $value = trim((string)($input[$field] ?? ''));
            $values[$field] = $value;
            if ($value !== '') {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
                if (!$date || $date->format('Y-m-d\TH:i') !== $value) {
                    $errors[$field] = t('Enter a valid date and time.');
                }
            }
        }

        if (empty($errors) && $values['date_started'] !== '' && $values['date_due'] !== '' &&
            strcmp($values['date_due'], $values['date_started']) < 0) {
            $errors['date_due'] = t('The end date cannot be before the start date.');
        }

        if (!empty($errors)) {
            $this->flash->failure(t('Review the planned dates.'));
            return $this->edit($values, $errors);
        }

        $updated = $this->taskModificationModel->update(array(
            'id' => (int)$task['id'],
            'date_started' => str_replace('T', ' ', $values['date_started']),
            'date_due' => str_replace('T', ' ', $values['date_due']),
        ));

        if (!$updated) {
            $this->flash->failure(t('Unable to save task planning.'));
            return $this->edit($values);
        }

        $this->flash->success(t('Task planning saved.'));
        return $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array(
            'task_id' => $task['id'], 'project_id' => $task['project_id']
        )), true);
    }
}
