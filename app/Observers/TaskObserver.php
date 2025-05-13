<?php

namespace App\Observers;

use App\Models\Task;

class TaskObserver
{
    public function created(Task $task): void
    {
        // mudar ou não o status da collection
    }



    public function updated(Task $task): void
    {
        // mudar ou não o status da collection
    }



    public function deleted(Task $task): void
    {
        // mudar ou não o status da collection
    }



    public function restored(Task $task): void
    {
        //
    }



    public function forceDeleted(Task $task): void
    {
        //
    }
}
