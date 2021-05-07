<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskTypeRequest;
use App\Role;
use App\TaskType;
use App\User;
use Illuminate\Http\Request;

/**
 * Контроллер типов задач
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class TaskTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:system-settings');
    }

    /**
     * Список типов задач
     *
     * @param TaskType $taskType
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(TaskType $taskType, Request $request)
    {
        $taskTypes = $taskType
            ->sortable(['name' => 'desc'])
            ->paginate(config('app.items_per_page'))
            ->appends($request->query());

        return view('task-types.index', compact('taskTypes'));
    }

    /**
     * Форма создания типа задачи
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $users = User::all()->pluck('name', 'id');
        $roles = Role::all()->pluck('name', 'id');

        return view('task-types.create', compact('users', 'roles'));
    }

    /**
     * Сохранение вновь создаваемого типа задачи
     *
     * @param TaskTypeRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TaskTypeRequest $request)
    {
        $taskType = TaskType::create($request->input());
        $taskType->balancerPriorityUsers()->sync($request->priority_users);
        $taskType->balancerPriorityRoles()->sync($request->priority_roles);
        $taskType->balancerDisabledUsers()->sync($request->disabled_users);

        return redirect()
            ->route('task-types.index')
            ->with('success', __('Task Type created successfully'));
    }

    /**
     * Форма редактирования типа задачи
     *
     * @param TaskType $taskType
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(TaskType $taskType)
    {
        $users = User::all()->pluck('name', 'id');
        $roles = Role::all()->pluck('name', 'id');

        return view('task-types.edit', compact('taskType', 'users', 'roles'));
    }

    /**
     * Сохранение изменного типа задач
     *
     * @param TaskTypeRequest $request
     * @param TaskType $taskType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TaskTypeRequest $request, TaskType $taskType)
    {
        $data = $request->input();
        $data['is_store'] = $data['is_store'] ?? 0;
        $data['is_basic'] = isset($data['is_basic']) ? $data['is_basic'] : 0;
        $taskType->update($data);
        $taskType->balancerPriorityUsers()->sync($request->priority_users);
        $taskType->balancerPriorityRoles()->sync($request->priority_roles);
        $taskType->balancerDisabledUsers()->sync($request->disabled_users);

        return redirect()
            ->route('task-types.index')
            ->with('success', __('Task Type updated successfully'));
    }
}
