<?php

namespace App\Http\Controllers;

use App\Exceptions\DoingException;
use App\Http\Requests\RoutePointStateRequest;
use App\OrderState;
use App\ProductExchangeState;
use App\ProductReturnState;
use App\RoutePoint;
use App\RoutePointState;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;

class RoutePointStateController extends Controller
{
    //TODO Надо пересмотреть всю систему разрешений к более общим
    public function __construct()
    {
        $this->middleware('permission:orderState-list', ['except' => 'action']);
        $this->middleware('permission:orderState-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:orderState-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Отображает список статусов маршрутных точек
     *
     * @return Response
     */
    public function index()
    {
        $routePointStates = RoutePointState::orderBy('id', 'DESC')->paginate(15);

        return view('route-point-states.index', compact('routePointStates'));
    }

    /**
     * Отображение формы для создания нового статуса
     *
     * @return Response
     */
    public function create()
    {
        $routePointStates = RoutePointState::all()->pluck('name', 'id');
        $orderStates = OrderState::all()->pluck('name', 'id');
        $productReturnStates = ProductReturnState::all()->pluck('name', 'id');
        $productExchangeStates = ProductExchangeState::all()->pluck('name', 'id');

        return view(
            'route-point-states.create',
            compact(
                'routePointStates',
                'orderStates',
                'productReturnStates',
                'productExchangeStates'
            )
        );
    }

    /**
     * Сохранение данных из формы создания нового статуса
     *
     * @param  RoutePointStateRequest $request
     * @return Response
     */
    public function store(RoutePointStateRequest $request)
    {
        $data = $request->input();

        $data['is_successful'] = isset($data['is_successful']) ? $data['is_successful'] : 0;
        $data['is_failure'] = isset($data['is_failure']) ? $data['is_failure'] : 0;
        $data['is_new'] = isset($data['is_new']) ? $data['is_new'] : 0;

        $routePointState = RoutePointState::create($data);
        $routePointState->previousStates()->sync($request->previous_states_id);

        return redirect()
            ->route('route-point-states.index')
            ->with('success', __('Route Point State created successfully'));
    }

    /**
     * Отображение формы редактирования статуса
     *
     * @param  RoutePointState $routePointState
     * @return Response
     */
    public function edit(RoutePointState $routePointState)
    {
        $routePointStates = RoutePointState::all()->pluck('name', 'id');
        $orderStates = OrderState::all()->pluck('name', 'id');
        $productReturnStates = ProductReturnState::all()->pluck('name', 'id');
        $productExchangeStates = ProductExchangeState::all()->pluck('name', 'id');

        return view(
            'route-point-states.edit',
            compact(
                'routePointState',
                'routePointStates',
                'orderStates',
                'productReturnStates',
                'productExchangeStates'
            )
        );
    }

    /**
     * Сохранение данных из формы редактирования статуса
     *
     * @param  RoutePointStateRequest $request
     * @param  RoutePointState $routePointState
     * @return Response
     */
    public function update(RoutePointStateRequest $request, RoutePointState $routePointState)
    {
        $data = $request->input();

        $data['is_successful'] = isset($data['is_successful']) ? $data['is_successful'] : 0;
        $data['is_failure'] = isset($data['is_failure']) ? $data['is_failure'] : 0;
        $data['is_new'] = isset($data['is_new']) ? $data['is_new'] : 0;

        $routePointState->update($data);
        $routePointState->previousStates()->sync($request->previous_states_id);

        return redirect()
            ->route('route-point-states.index')
            ->with('success', __('Route Point State updated successfully'));
    }

    /**
     * Смена статуса маршрутной точки из маршрутного листа
     * @param RoutePoint $routePoint
     * @param RoutePointState $routePointState
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function action(RoutePoint $routePoint, RoutePointState $routePointState, Request $request)
    {
        if ($routePoint->routeList && $routePoint->routeList->courier_id !== \Auth::id()) {
            throw UnauthorizedException::forPermissions([]);
        }

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {

            $routePoint->states()->save($routePointState);

            if ($routePointState->is_need_comment_to_point_object && $request->comment) {
                $user = \Auth::user();

                $routePoint->pointObject->update(
                    [
                        'comment' => "{$routePoint->pointObject->comment}\n\r{$user->name}:{$request->comment}",
                    ]
                );

            }

        } catch (\Exception $exception) {

            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            } else {
                throw $exception;
            }

        }


        DB::commit();

        $htmlResult = '';

        if (!is_null(RoutePoint::find($routePoint->id))) {
            $routePoint->refresh();
            $htmlResult = view(
                'route-lists-own._partials.route-point.row',
                compact('routePoint')
            )->render();
        }

        return response()->json(
            [
                'okState' => preg_replace('/\s+\(.+\)/', '', $routePoint->currentState()->name),
                'html' => $htmlResult,
            ]
        );

    }
}
