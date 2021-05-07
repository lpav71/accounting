<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskPriorityRequest;
use App\TaskPriority;
use Illuminate\Http\Request;

class TaskPriorityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:task-edit');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\TaskPriority $taskPriority
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(TaskPriority $taskPriority, Request $request)
    {
        $taskPriorities = $taskPriority->sortable(['rate' => 'desc'])->paginate(25)->appends(
            $request->query()
        );

        return view('task-priorities.index', compact('taskPriorities'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('task-priorities.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\TaskPriorityRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(TaskPriorityRequest $request)
    {
        TaskPriority::create($request->input());

        return redirect()->route('task-priorities.index')->with('success', __('Task Priority created successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TaskPriority $taskPriority
     * @return \Illuminate\Http\Response
     */
    public function edit(TaskPriority $taskPriority)
    {
        return view('task-priorities.edit', compact('taskPriority'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\TaskPriorityRequest $request
     * @param  \App\TaskPriority $taskPriority
     * @return \Illuminate\Http\Response
     */
    public function update(TaskPriorityRequest $request, TaskPriority $taskPriority)
    {
        $data = $request->input();
        $data['is_urgent'] = $data['is_urgent'] ?? 0;
        $data['is_very_urgent'] = $data['is_very_urgent'] ?? 0;
        $data['is_normal'] = $data['is_normal'] ?? 0;
        $taskPriority->update($data);

        return redirect()->route('task-priorities.index')->with('success', __('Task Priority updated successfully'));
    }
}
