<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskCommentRequest;
use App\Task;
use App\TaskComment;
use Auth;

class TaskCommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:task-edit');
    }

    public function store(TaskCommentRequest $request, Task $task)
    {
        $data = $request->input();
        $data['user_id'] = Auth::id();
        TaskComment::create($data);

        return redirect()->route('tasks.edit', $task)->with('success', __('Comment added successfully'));
    }
}
