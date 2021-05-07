<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskStateRequest;
use App\TaskState;

class TaskStateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:task-manage');
    }

    public function index()
    {
        $taskStates = TaskState::orderBy('id', 'DESC')->paginate(10);

        return view('task-states.index', compact('taskStates'));
    }

    public function create()
    {
        $taskStates = TaskState::all()->pluck('name', 'id');

        return view('task-states.create', compact('taskStates'));
    }

    public function store(TaskStateRequest $request)
    {
        $data = $request->input();
        if (!isset($data['is_new'])) $data['is_new'] = 0;
        $data['previous_task_states_id'] = isset($data['previous_task_states_id']) ? $data['previous_task_states_id'] : [];

        $taskState = TaskState::create($data);
        $taskState->previousStates()->sync($data['previous_task_states_id']);

        return redirect()->route('task-states.index')->with('success', __('Task State created successfully'));
    }

    public function edit(TaskState $taskState)
    {
        $taskStates = TaskState::all()->pluck('name', 'id');

        return view('task-states.edit', compact('taskState', 'taskStates'));
    }

    public function update(TaskStateRequest $request, TaskState $taskState)
    {
        $data = $request->input();
        if (!isset($data['is_new'])) $data['is_new'] = 0;
        $data['previous_task_states_id'] = isset($data['previous_task_states_id']) ? $data['previous_task_states_id'] : [];

        $taskState->update($data);
        $taskState->previousStates()->sync($data['previous_task_states_id']);

        return redirect()->route('task-states.index')->with('success', __('Task State updated successfully'));
    }
}
