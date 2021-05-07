<?php

namespace App\Http\Controllers;

use App\Certificate;
use App\Exceptions\DoingException;
use App\Http\Requests\CashboxOperationRequest;
use App\Operation;
use App\Currency;
use App\Cashbox;
use App\OperationState;
use App\Order;
use App\ProductExchange;
use App\ProductReturn;
use App\User;
use Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use DB;

class CashboxOperationController extends Controller
{

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->middleware('permission:cashbox-operation');
    }

    /**
     * Форма создания новой операции
     *
     * @param  \App\Cashbox $cashbox
     * @return \Illuminate\Http\Response
     */
    public function create(Cashbox $cashbox)
    {
        if (!$cashbox->users()->find(Auth::user()->getAuthIdentifier())) {
            throw UnauthorizedException::forPermissions([]);
        }

        $currencies = Currency::all()->pluck('name', 'id');

        $orders = Order::all()
            ->map(function (Order $order) {
                return [
                    'id' => $order->getDisplayNumber() . " #".$order->getOrderNumber(),
                    'real_id' => $order->id,
                ];
            })
            ->pluck('id', 'real_id')
            ->prepend(__('No'), 0);

        $certificates = Certificate::all()
            ->pluck('number', 'id')
            ->prepend(__('No'), 0);

        $productReturns = ProductReturn::all()
            ->map(function (ProductReturn $productReturn) {
                return [
                    'id' => $productReturn->id,
                    'full_name' => "{$productReturn->order->getDisplayNumber()} > {$productReturn->id}",
                ];
            })
            ->pluck('full_name', 'id')
            ->prepend(__('No'), 0);

        $productExchanges = ProductExchange::all()
            ->map(function (ProductExchange $productExchange) {
                return [
                    'id' => $productExchange->id,
                    'full_name' => "{$productExchange->order->getDisplayNumber()} > {$productExchange->id}",
                ];
            })
            ->pluck('full_name', 'id')
            ->prepend(__('No'), 0);

        return view(
            'cashbox-operations.create',
            compact(
                'cashbox',
                'currencies',
                'orders',
                'productReturns',
                'productExchanges',
                'certificates'
            )
        );
    }

    /**
     * Сохранение данных из формы создания новой операции
     *
     * @param CashboxOperationRequest $request
     * @param Cashbox $cashbox
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function store(CashboxOperationRequest $request, Cashbox $cashbox)
    {
        /**
         * @var User $user
         */
        $user = Auth::user();

        if (!$cashbox->users()->find($user->id)) {
            throw UnauthorizedException::forPermissions([]);
        }

        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();

        try {
            Operation::create(
                array_merge(
                    $request->input(),
                    [
                        'user_id' => $user->id,
                        'operable_type' => Currency::class,
                        'operable_id' => $request->currency_id,
                        'storage_type' => Cashbox::class,
                        'storage_id' => $cashbox->id,
                    ]
                )
            )->states()->sync(OperationState::where('non_confirmed', '=', 1)->first()->id);
        } catch (\Exception $exception) {
            if ($exception instanceof DoingException) {
                DB::rollback();
                throw ValidationException::withMessages($exception->getMessages());
            }

            throw $exception;
        }

        DB::commit();

        return redirect()->route(
            $user->hasPermissionTo('cashbox-list') ? 'cashboxes.show' : 'own-cashboxes.show',
            ['cashbox' => $cashbox]
        );
    }
}
