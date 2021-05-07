<?php

namespace App\Observers;

use App\Order;
use App\TaskComment;

class TaskCommentObserver
{
    /**
     * Handle the task comment "created" event.
     *
     * @param  \App\TaskComment $taskComment
     * @return void
     */
    public function created(TaskComment $taskComment)
    {
        if ($taskComment->task->order && $taskComment->task->order instanceof Order) {
            $taskComment->task->order->comments()->create([
                'comment' => __('Comment added to task')." #task-{$taskComment->task->id}: {$taskComment->comment}",
                'user_id' => \Auth::id() ?: null,
            ]);
        }
    }
}
