<?php

namespace App\Http\Controllers;

use App\Cashbox;
use App\Currency;
use App\Exceptions\DoingException;
use App\Filters\OperationFilter;
use App\Http\Requests\CashboxRequest;
use App\Operation;
use App\OperationState;
use App\Role;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;

class CashboxController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:cashbox-list', ['only' => ['index']]);
        $this->middleware('permission:cashbox-show-own', ['except' => ['index', 'create', 'store', 'edit', 'update', 'destroy']]);
        $this->middleware('permission:cashbox-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:cashbox-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:cashbox-delete', ['only' => ['destroy']]);
        $this->middleware('permission:cashbox-search', ['only' => ['cashboxSearch']]);
    }

    /**
     * Отображение списка касс
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Cashbox $cashbox, Request $request)
    {
        $cashboxes = $cashbox
        ->sortable(['name' => 'asc'])
        ->paginate(100)
        ->appends($request->query());

        return view('cashboxes.index', compact('cashboxes'));
    }

    /**
     * Отображение формы создания кассы
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = User::pluck('name', 'id');

        return view('cashboxes.create', compact('users'));
    }

    /**
     * Отображение формы поиска операций
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function cashboxSearch()
    {
        $cashboxes = Cashbox::all()->pluck('name', 'id');
        $users = User::pluck('name', 'id');
        $roles = Role::pluck('name', 'id');

        return view('cashboxes.search', compact('cashboxes', 'users', 'roles'));
    }

    /**
     * Обработка формы поиска операций
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showSearchOperation(Request $request)
    {
        if($request->cashbox_id) {
            $cashboxes = Cashbox::whereIn('id', $request->cashbox_id)->get();
        } else {
            $cashboxes = Cashbox::all();
        }
        $operations = collect();
        foreach ($cashboxes as $cashbox) {
            $cashboxOperations = $cashbox->operations();
            if($request->phrase) {
                $cashboxOperations->where('comment', 'like', '%' . $request->phrase . '%');
            }
            if($request->users_id && !empty($request->users_id)) {
                $cashboxOperations->whereIn('user_id', $request->users_id);
            }
            if($request->operation_type && $request->operation_type !== "A") {
                $cashboxOperations->where('type', '=', $request->operation_type);
            }
            if($request->sum) {
                $cashboxOperations->where('quantity', '=', $request->sum);
            }
            $cashboxOperations = $cashboxOperations->get();
            if($request->roles && !empty($request->roles)) {
                $roles = $request->roles;
                $cashboxOperations = $cashboxOperations->filter(function (\App\Operation $operation) use ($roles){
                    $user = User::find($operation->user_id);
                    foreach ($user->roles->pluck('id') as $role) {
                        return in_array($role, $roles);
                    }
                });
            }
            $dateFrom = $request->from ? Carbon::createFromFormat('d-m-Y', $request->from)->format('d-m-Y') : null;
            $dateTo = $request->to ? Carbon::createFromFormat('d-m-Y', $request->to)->format('d-m-Y') : null;
            if($dateFrom && $dateTo) {
                $cashboxOperations = $cashboxOperations->filter(function (Operation $operation) use ($dateFrom, $dateTo){
                    if(strtotime($operation->created_at->format('d-m-Y')) >= strtotime($dateFrom) && strtotime($operation->created_at->format('d-m-Y')) <= strtotime($dateTo)) {
                        return true;
                    }
                });
            } elseif ($dateFrom) {
                $cashboxOperations = $cashboxOperations->filter(function (Operation $operation) use ($dateFrom){
                    if(strtotime($operation->created_at->format('d-m-Y')) >= strtotime($dateFrom)) {
                        return true;
                    }
                });
            } elseif ($dateTo) {
                $cashboxOperations = $cashboxOperations->filter(function (Operation $operation) use ($dateTo){
                    if(strtotime($operation->created_at->format('d-m-Y')) <= strtotime($dateTo)) {
                        return true;
                    }
                });
            }
            $operations->push($cashboxOperations);
        }
        $operations = $operations->collapse();

        $sum = 0;
        /**
         * @var $operation Operation
         */
        foreach ($operations as $operation) {
            $sum += $operation->quantity;
        }

        $operations = $this->paginate($operations, Auth::user()->count_operation ? Auth::user()->count_operation : 15);
        $operations->setPath($request->url());

        return view('cashboxes.show-operations', compact('operations', 'sum'));
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
     * Сохранение данных из формы создания кассы
     *
     * @param  CashboxRequest $request
     * @return Response
     */
    public function store(CashboxRequest $request)
    {
        $this->validate(
            $request,
            [
                'user_id_with_transfer_rights' => 'array',
                'user_id_with_confirm_rights' => 'array',
            ]
        );

        $cashbox = Cashbox::create($request->input());
        $cashbox->users()->sync($request->user_id);
        $cashbox->usersWithTransferRights()->sync($request->user_id_with_transfer_rights);
        $cashbox->userWithConfirmedRights()->sync($request->user_id_with_confirm_rights);

        return redirect()
            ->route('cashboxes.index')
            ->with('success', __('Cashbox created successfully'));
    }

    /**
     * Подтверждение кассовых операций из поиска операций
     *
     * @param Operation $operation
     */
    public function confirmOperationAjax(Operation $operation)
    {
        $operation->states()->sync(OperationState::where('is_confirmed', '=', 1)->first()->id);
    }

    /**
     * Просмотр кассы
     *
     * @param Cashbox $cashbox
     * @param OperationFilter $filters
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Cashbox $cashbox, OperationFilter $filters)
    {
        $users = User::pluck('name', 'id');
        $operations = $cashbox->operations()->filter($filters)->orderBy('id', 'DESC')->paginate(Auth::user()->count_operation ? Auth::user()->count_operation : 15)->appends(\Request::except('page'));

        return view('cashboxes.show', compact('cashbox', 'users', 'operations'));
    }

    /**
     * Просмотр собственной кассы
     *
     * @param Cashbox $cashbox
     * @param OperationFilter $filters
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showOwn(Cashbox $cashbox, OperationFilter $filters)
    {
        if (is_null($cashbox->users()->find(Auth::id()))) {
            throw UnauthorizedException::forPermissions([]);
        }

        return $this->show($cashbox, $filters);
    }

    /**
     * Отображение формы редактирования кассы
     *
     * @param  Cashbox $cashbox
     * @return Response
     */
    public function edit(Cashbox $cashbox)
    {
        $users = User::pluck('name', 'id');

        return view('cashboxes.edit', compact('cashbox', 'users'));
    }

    /**
     * Обновление данных из формы редактирования кассы
     *
     * @param  CashboxRequest $request
     * @param  Cashbox $cashbox
     * @return Response
     */
    public function update(CashboxRequest $request, Cashbox $cashbox)
    {
        $data = $request->input();
        $data['is_non_cash'] = isset($data['is_non_cash']) ? $data['is_non_cash'] : 0;
        $data['for_certificates'] = isset($data['for_certificates']) ? $data['for_certificates'] : 0;

        $cashbox->update($data);
        $cashbox->users()->sync($request->user_id);
        $cashbox->usersWithTransferRights()->sync($request->user_id_with_transfer_rights);
        $cashbox->userWithConfirmedRights()->sync($request->user_id_with_confirm_rights);

        return redirect()
            ->route('cashboxes.index')
            ->with('success', __('Cashbox updated successfully'));
    }

    /**
     * Удаление кассы
     *
     * @param Cashbox $cashbox
     * @return RedirectResponse
     * @throws \Exception
     */
    public function destroy(Cashbox $cashbox)
    {
        if ($cashbox->operations->isNotEmpty()) {
            return redirect()
                ->route('cashboxes.index')
                ->with(
                    'warning',
                    __('This Cashbox can not be deleted, because it have operations.')
                );
        }

        if ($cashbox->users->isNotEmpty()) {
            return redirect()
                ->route('cashboxes.index')
                ->with(
                    'warning',
                    __('This Cashbox can not be deleted, because it have users.')
                );
        }

        $cashbox->delete();

        return redirect()
            ->route('cashboxes.index')
            ->with('success', __('Cashbox deleted successfully'));
    }

    /**
     * Отображение формы переноса валюты
     *
     * @param Cashbox $cashbox
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function transfer(Cashbox $cashbox)
    {
        return view('cashboxes.transfer', compact('cashbox'));
    }

    /**
     * Перенос валюты
     *
     * @param Cashbox $cashbox
     * @param Request $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function transferCashbox(Cashbox $cashbox, Request $request)
    {
        $this->validate(
            $request,
            [
                'cashbox_id' => 'required',
                'summ' => 'required|integer|min:1',
                'comment' => 'required|string|min:10',
            ]
        );

        $toCashbox = Cashbox::find($request->cashbox_id);

        $user = Auth::user();
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {
            Operation::create(
                array_merge(
                    $request->input(),
                    [
                        'type' => 'C',
                        'comment' => 'Перенос в кассу ' . $toCashbox->name . ': ' . $request->comment,
                        'user_id' => $user->id,
                        'order_id' => 0,
                        'quantity' => $request->input('summ'),
                        'order_detail_id' => 0,
                        'is_reservation' => 0,
                        'operable_type' => Currency::class,
                        'operable_id' => 1,
                        'storage_type' => Cashbox::class,
                        'storage_id' => $cashbox->id,
                        'is_transfer' => 1,
                    ]
                )
            )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);

            Operation::create(
                array_merge(
                    $request->input(),
                    [
                        'type' => 'D',
                        'comment' => 'Перенос из кассы ' . $request->name .': ' . $request->comment,
                        'user_id' => $user->id,
                        'order_id' => 0,
                        'order_detail_id' => 0,
                        'is_reservation' => 0,
                        'quantity' => $request->input('summ'),
                        'operable_type' => Currency::class,
                        'operable_id' => 1,
                        'storage_type' => Cashbox::class,
                        'storage_id' => $request->cashbox_id,
                        'is_transfer' => 1,
                    ]
                )
            )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);
        }  catch (\Exception $exception) {
            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            }

            throw $exception;

        }

        DB::commit();

        return redirect()->route('cashbox.transfer', compact('cashbox'));
    }

    /**
     * Скрывать кассу в меню
     *
     * @param Cashbox $cashbox
     * @return RedirectResponse
     */
    public function hideInMenu(Cashbox $cashbox)
    {
        $cashbox->is_hidden  = 1;
        $cashbox->save();
        return redirect()
            ->route('cashboxes.index');
    }

    /**
     * Показывать кассу в меню
     *
     * @param Cashbox $cashbox
     * @return RedirectResponse
     */
    public function showInMenu(Cashbox $cashbox)
    {
        $cashbox->is_hidden  = 0;
        $cashbox->save();
        return redirect()
            ->route('cashboxes.index');
    }
}
