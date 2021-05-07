<?php

namespace App\Http\Controllers;

use App\CourierTask;
use App\CourierTaskState;
use App\RoutePoint;
use Illuminate\Http\Request;

class CourierTaskController extends Controller
{
    /**
     * CourierTaskController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:courier-task');
        $this->middleware('permission:courier-task-list', ['only' => 'index']);
        $this->middleware('permission:courier-task-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:courier-task-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Отображение задач для курьеров
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $courierTask = CourierTask::orderBy('id', 'DESC')->paginate(25);

        return view('courier-tasks.index', compact('courierTask'));
    }

    /**
     * Отображение формы создания задачи для курьера
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('courier-tasks.create');
    }

    /**
     * Обработка формы создания задачи для курьера
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'date' => 'string|required',
            'address' => 'string|required',
            'city' => 'string|required',
            'comment' => 'string|required',
            'start_time' => 'string',
            'end_time' => 'string',
            'city_id' => 'int|required'
        ]);

        $courierTask = CourierTask::create($request->input());

        $courierTask->states()->save(CourierTaskState::where('is_new',1)->first());

        return redirect()->route('courier-tasks.index')->with('success', __('Courier task created successfully'));
    }

    /**
     * Отображение формы редактирования задачи для курьера
     *
     * @param CourierTask $courierTask
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(CourierTask $courierTask)
    {
        $courierTaskStates = CourierTaskState::all()->filter(function (CourierTaskState $courierTaskState) use ($courierTask){
            return $courierTaskState->previousStates()->where('id', $courierTask->currentState()['id']);
        })->pluck('name', 'id')->prepend($courierTask->currentState()['name'], $courierTask->currentState()['id']);

        return view('courier-tasks.edit', compact('courierTask', 'courierTaskStates'));
    }

    /**
     * Обработка формы редактирования задачи для курьера
     *
     * @param Request $request
     * @param CourierTask $courierTask
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CourierTask $courierTask)
    {
        $this->validate($request, [
            'date' => 'string|required',
            'address' => 'string|required',
            'city' => 'string|required',
            'comment' => 'string|required',
            'start_time' => 'string',
            'end_time' => 'string',
            'city_id' => 'int|required'
        ]);

        $courierTask->update($request->input());
        if($request->courier_task_state_id) {
            $courierTask->states()->save(CourierTaskState::find($request->courier_task_state_id));
        }

        return redirect()->route('courier-tasks.index')->with('success', __('Courier task edited successfully'));
    }
}
