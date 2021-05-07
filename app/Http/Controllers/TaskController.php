<?php

namespace App\Http\Controllers;

use App\Channel;
use App\Customer;
use App\Filters\TaskFilter;
use App\Http\Requests\TaskRequest;
use App\Order;
use App\Task;
use App\TaskPriority;
use App\TaskState;
use App\TaskType;
use App\User;
use Auth;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Контроллер задач
 *
 * @package App\Http\Controllers
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:task-list');
        $this->middleware('permission:task-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:task-create', ['only' => ['create', 'store']]);
    }

    /**
     * Отображает список задач
     *
     * @param Task $task
     * @param TaskFilter $filters
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Task $task, TaskFilter $filters, Request $request)
    {
        $tasks = $task->filter($filters)->sortable(['id' => 'desc'])->paginate(25)->appends(
            $request->query()
        );

        $channels = Channel::all()->pluck('name', 'name')->prepend(__('Different'), __('Different'))->prepend(__('No'), 0);

        if ($request->channel) {
            $tmp = collect();
            if ($request->channel == __('Different')) {
                foreach ($tasks as $task) {
                    $customer = $task->order->customer;
                    $orders = $customer->orders->filter(function (Order $order){
                        return !$order->is_hidden && !$order->currentState()->is_failure && !$order->currentState()->is_successful;
                    });

                    if (count($orders) > 1) {
                        $channelName = $orders->first()->channel->name;
                        foreach ($orders as $order) {
                            if ($order->channel->name != $channelName) {
                                $tmp->push($task);
                            }
                        }
                    }
                }
            } else {
                foreach ($tasks as $task) {
                    if ($task->order->channel->name == $request->channel) {
                        $tmp->push($task);
                    }
                }
            }

            $tasks = $this->paginate($tmp, 25);
        }



        return view('tasks.index', compact('tasks', 'channels'));
    }

    /**
     * @param $items
     * @param int $perPage
     * @param null $page
     * @param array $options
     * @return LengthAwarePaginator
     */
    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    /**
     * Отображает список актуальных задач пользователя
     *
     * @param TaskFilter $filters
     * @param User $user
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function actualForUser(TaskFilter $filters, User $user)
    {
        $tasks = Task::select('tasks.*')
            ->join('task_priorities', 'tasks.task_priority_id', '=', 'task_priorities.id')
            ->join('task_types', 'tasks.task_type_id', '=', 'task_types.id')
            ->where(
                'tasks.deadline_date',
                '<',
                Carbon::now()->addDay()->setTime(0, 0, 0, 0)->toDateTimeString()
            )
            ->leftJoinSub(
                function (Builder $query) {
                    return $query
                        ->selectRaw('`task_task_state`.*')
                        ->from('task_task_state')
                        ->leftJoinSub(function (Builder $query) {
                            return $query
                                ->selectRaw('task_id, MAX(id) as id')
                                ->from('task_task_state')
                                ->groupBy('task_id');
                        },
                            'task_task_state_j',
                            'task_task_state_j.id',
                            '=',
                            'task_task_state.id')
                        ->whereNotNull('task_task_state_j.task_id');
                },
                'task_task_state',
                'tasks.id',
                '=',
                'task_task_state.task_id'
            )
            ->leftJoin('task_states', 'task_states.id', '=', 'task_task_state.task_state_id')
            ->where('tasks.performer_user_id', $user->id)
            ->where('task_states.is_closed', 0)
            ->orderByDesc('tasks.deadline_date')
            ->orderByDesc('task_priorities.rate')
            ->orderBy('tasks.deadline_time')
            ->whereIn('tasks.id', (new Task())->filter($filters)->pluck('id'))
            ->paginate(25);

            $isForUser = true;

        return view('tasks.actual', compact('tasks', 'isForUser'));
    }

    /**
     * Отображает список актуальных задач
     *
     * @param TaskFilter $filters
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function actual(TaskFilter $filters)
    {
        $tasks = Task::select('tasks.*')
            ->join('task_priorities', 'tasks.task_priority_id', '=', 'task_priorities.id')
            ->join('task_types', 'tasks.task_type_id', '=', 'task_types.id')
            ->where(
                'tasks.deadline_date',
                '<',
                Carbon::now()->addDay()->setTime(0, 0, 0, 0)->toDateTimeString()
            )
            ->leftJoinSub(
                function (Builder $query) {
                    return $query
                        ->selectRaw('`task_task_state`.*')
                        ->from('task_task_state')
                        ->leftJoinSub(function (Builder $query) {
                            return $query
                                ->selectRaw('task_id, MAX(id) as id')
                                ->from('task_task_state')
                                ->groupBy('task_id');
                        },
                            'task_task_state_j',
                            'task_task_state_j.id',
                            '=',
                            'task_task_state.id')
                        ->whereNotNull('task_task_state_j.task_id');
                },
                'task_task_state',
                'tasks.id',
                '=',
                'task_task_state.task_id'
            )
            ->leftJoin('task_states', 'task_states.id', '=', 'task_task_state.task_state_id')
            ->where('task_states.is_closed', 0)
            ->orderByDesc('tasks.deadline_date')
            ->orderByDesc('task_priorities.rate')
            ->orderBy('tasks.deadline_time')
            ->whereIn('tasks.id', (new Task())->filter($filters)->pluck('id'))
            ->paginate(25);

        $isForUser = false;

        return view('tasks.actual', compact('tasks', 'isForUser'));
    }

    /**
     * Отображение формы для создания новой задачи
     *
     * @param Order $order
     * @param Customer $customer
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Order $order, Customer $customer)
    {
        $customers = Customer::all()->reduce(
            function (Collection $carry, Customer $customer) {
                $carry->put($customer->id, $customer->full_name);

                return $carry;
            },
            collect([0 => __('No')])
        );
        $orders = Order::all()->reduce(
            function (Collection $carry, Order $order) {
                $carry->put($order->id, $order->getDisplayNumber());

                return $carry;
            },
            collect([0 => __('No')])
        );
        $users = User::all()->pluck('name', 'id')->prepend(__('No'), null);
        $taskStates = TaskState::all()->filter(
            function (TaskState $taskState) {
                return !$taskState->previousStates()->count();
            }
        )->pluck('name', 'id');
        $priorities = TaskPriority::all()->pluck('name', 'id');
        $types = TaskType::all()->pluck('name', 'id');

        $data = compact('customers', 'orders', 'users', 'taskStates', 'priorities', 'types');

        $data['order'] = $order->id ? $order : null;
        $customer = $order->id ? $order->customer : $customer;
        $data['customer'] = $customer->id ? $customer : null;

        return view('tasks.create', $data);
    }

    /**
     * Сохранение данных из формы создания новой задачи
     *
     * @param TaskRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TaskRequest $request)
    {
        $task = Task::create($request->input());
        $task->states()->save(TaskState::find($request->task_state_id),['user_id'=> Auth::id()]);

        return redirect()->route('tasks.edit', $task)->with('success', __('Task created successfully'));
    }

    /**
     * Отображение формы редактирования задачи
     *
     * @param Task $task
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Task $task)
    {
        $task->update(
            [
                'last_open_time' => Carbon::now(),
            ]
        );

        $customers = Customer::all()->reduce(
            function (Collection $carry, Customer $customer) {
                $carry->put($customer->id, $customer->full_name);

                return $carry;
            },
            collect([0 => __('No')])
        );
        $orders = Order::all()->reduce(
            function (Collection $carry, Order $order) {
                $carry->put($order->id, $order->getDisplayNumber());

                return $carry;
            },
            collect([0 => __('No')])
        );
        $users = User::all()->pluck('name', 'id')->prepend(__('No'), null);
        $priorities = TaskPriority::all()->pluck('name', 'id');
        $types = TaskType::all()->pluck('name', 'id');
        if ($task->currentState()) {
            $taskStates = TaskState::all()->filter(
                function (TaskState $taskState) use ($task) {
                    return $taskState->previousStates()->where('task_states.id', $task->currentState()['id'])->count();
                }
            )->pluck('name', 'id')->prepend($task->currentState()['name'], $task->currentState()['id']);
        } else {
            $taskStates = TaskState::all()->filter(
                function (TaskState $taskState) {
                    return !$taskState->previousStates()->count();
                }
            )->pluck('name', 'id');
        }
        $taskComments = $task->comments;


        return view(
            'tasks.edit',
            compact('task', 'customers', 'orders', 'users', 'taskStates', 'taskComments', 'priorities', 'types')
        );
    }

    /**
     * Сохранение данных из формы редактирования задачи
     *
     * @param TaskRequest $request
     * @param Task $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TaskRequest $request, Task $task)
    {
        $task->update($request->input());
        $newState = TaskState::find($request->task_state_id);
        if (!is_object($task->currentState()) || $newState->id !== $task->currentState()->id) {
            $task->states()->save($newState,['user_id'=> Auth::id()]);
        }

        return redirect()->route('tasks.edit', $task)->with('success', __('Task updated successfully'));
    }
}
