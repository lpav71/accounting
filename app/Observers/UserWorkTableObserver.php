<?php

namespace App\Observers;

use App\Jobs\ReassignTasksPerformers;
use App\UserWorkTable;

class UserWorkTableObserver
{
    /**
     * Обработка события 'created'
     *
     * @param UserWorkTable $userWorkTable
     */
    public function created(UserWorkTable $userWorkTable)
    {
        $this->reassignTasksPerformersIfNeeded($userWorkTable);
    }

    /**
     * Обработка события 'updated'
     *
     * @param UserWorkTable $userWorkTable
     */
    public function updated(UserWorkTable $userWorkTable)
    {
        $this->reassignTasksPerformersIfNeeded($userWorkTable);
    }

    /**
     * Постановка задачи на переназначение отвественных при необходимости
     *
     * @param UserWorkTable $userWorkTable
     */
    protected function reassignTasksPerformersIfNeeded(UserWorkTable $userWorkTable): void
    {
        if ($userWorkTable->is_working) {
            ReassignTasksPerformers::dispatch();
        }
    }
}
