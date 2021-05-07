<?php


namespace App\Http\Controllers;

use App\CourierTaskState;
use Illuminate\Http\Request;

class CourierTaskStateController extends Controller
{
    /**
     * Список статусов курьерских задач
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $courierTaskStates = CourierTaskState::orderBy('id', 'DESC')->paginate(15);

        return view('courier-task-states.index', compact('courierTaskStates'));
    }

    /**
     * Отображение формы создания статуса курьерской задачи
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $courierTaskStates = CourierTaskState::all()->pluck('name', 'id');

        return view('courier-task-states.create', compact('courierTaskStates'));
    }

    /**
     * Обработка формы создания статуса курьерской задачи
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->input();

        $data['is_successful'] = isset($data['is_successful']) ? $data['is_successful'] : 0;
        $data['is_failure'] = isset($data['is_failure']) ? $data['is_failure'] : 0;
        $data['is_new'] = isset($data['is_new']) ? $data['is_new'] : 0;
        $data['is_courier_state'] = isset($data['is_courier_state']) ? $data['is_courier_state'] : 0;

        $courierTaskState = CourierTaskState::create($data);
        $courierTaskState->previousStates()->sync($request->previous_states_id);

        return redirect()->route('courier-task-states.index')
            ->with('success', __('Create courier task state successfully'));
    }

    /**
     * Отображение формы редактирования статуса курьерской задачи
     *
     * @param CourierTaskState $courierTaskState
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(CourierTaskState $courierTaskState)
    {
        $courierTaskStates = CourierTaskState::all()->pluck('name', 'id');

        return view('courier-task-states.edit', compact('courierTaskState', 'courierTaskStates'));
    }

    /**
     * Обработка формы редактирования статуса курьерской задачи
     *
     * @param Request $request
     * @param CourierTaskState $courierTaskState
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CourierTaskState $courierTaskState)
    {
        $data = $request->input();

        $data['is_successful'] = isset($data['is_successful']) ? $data['is_successful'] : 0;
        $data['is_failure'] = isset($data['is_failure']) ? $data['is_failure'] : 0;
        $data['is_new'] = isset($data['is_new']) ? $data['is_new'] : 0;
        $data['is_courier_state'] = isset($data['is_courier_state']) ? $data['is_courier_state'] : 0;

        $courierTaskState->update($data);
        $courierTaskState->previousStates()->sync($request->previous_states_id);

        return redirect()->route('courier-task-states.index')->with('success', __('Edit courier task state successfully'));
    }

    /**
     * Удаление статуса курьерской задачи
     *
     * @param CourierTaskState $courierTaskState
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(CourierTaskState $courierTaskState)
    {
        $courierTaskState->courierTasks()->detach();
        $courierTaskState->delete();

        return redirect()->route('courier-task-states.index')->with('success', __('Delete courier task state successfully'));
    }
}