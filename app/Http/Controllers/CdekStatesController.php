<?php

namespace App\Http\Controllers;

use App\Task;
use App\TaskType;
use Illuminate\Http\Request;
use App\CdekState;
use App\TaskPriority;
use Illuminate\Validation\Rule;

class CdekStatesController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:cdek-states-list');
        $this->middleware('permission:cdek-states-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('cdek-states.index', ['states' => CdekState::orderBy('id', 'ASC')->paginate(config('app.items_per_page'))]);
    }

    /**
     * @param CdekState $cdekState
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(CdekState $cdekState)
    {
        return view('cdek-states.show', compact('cdekState'));
    }

    /**
     * @param CdekState $cdekState
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(CdekState $cdekState)
    {
        $task_priorities = TaskPriority::all()->pluck('name', 'id');
        $taskTypes = TaskType::all()->pluck('name', 'id');

        return view('cdek-states.edit', compact('cdekState', 'task_priorities', 'taskTypes'));
    }

    /**
     * @param Request $request
     * @param CdekState $cdekState
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CdekState $cdekState)
    {
        $this->validate(
            $request,
            [
                'name' => [
                    'required',
                    Rule::unique('cdek_states', 'name')->ignore($cdekState->id),
                ],
                'state_code' => [
                    'required',
                    Rule::unique('cdek_states', 'state_code')->ignore($cdekState->id),
                ],
            ]
        );
        $data = $request->input();
        $data['need_task'] = $data['need_task'] ?? 0;
        $data['is_daily'] = $data['is_daily'] ?? 0;
        $data['is_last_state'] = $data['is_last_state'] ?? 0;
        $data['task_date_diff'] = (int)($data['task_date_diff'] ?? 0);
        $cdekState->update($data);

        return redirect()->route('cdek-states.index')->with('success', __('State updated successfully'));
    }
}
