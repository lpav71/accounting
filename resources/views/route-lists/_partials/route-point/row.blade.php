<tbody data-container="route-point-row">
<tr
        @if($routePoint->point_object_type == \App\CourierTask::class)
        style="background: wheat">
        @else
        style="background: {{ $routePoint->pointObject->currentState()->color }}">
        @endif
    @switch($routePoint->point_object_type)
        @case(\App\Order::class)

        <td class="align-middle">
            {{ __('Order') }}
        </td>

        @break
        @case(\App\ProductReturn::class)

        <td class="align-middle">
            {{ __('Return') }}
        </td>

        @break
        @case(\App\ProductExchange::class)

        <td class="align-middle">
            {{ __('Exchange') }}
        </td>

        @break
        @case(\App\CourierTask::class)
        <td class="align-middle">
            {{ __('Courier task') }}
        </td>
                @break
        @default

        <td class="align-middle">
            {{ __('Point') }}
        </td>

        @break
    @endswitch
    <td class="align-middle">
        <div class="text-nowrap rounded pl-1 pr-1"
             style="background-color: {{ $routePoint->currentState()->color }};">
            {{ $routePoint->id }}
        </div>
    </td>
    <td class="align-middle">
        <div class="text-nowrap rounded pl-1 pr-1"
             style="background-color: {{ $routePoint->currentState()->color }};">
            {{ $routePoint->currentState()->name }}
        </div>
    </td>
            <td class="text-nowrap align-middle">
                @if($routePoint->point_object_type == \App\CourierTask::class)
                    {{ implode(' ', [$routePoint->pointObject->start_time, $routePoint->pointObject->end_time]) }}
                @else
                    {{ implode(' ', [$routePoint->pointObject->delivery_start_time, $routePoint->pointObject->delivery_end_time]) }}
                @endif

            </td>
    <td class="w-100 align-middle">
        {{ $routePoint->getFullAddress() }}
    </td>
    <td class="align-middle">
        @switch($routePoint->point_object_type)
            @case(\App\Order::class)
            <a
                    href="{{ route('orders.edit', $routePoint->pointObject->id) }}"
                    target="_blank"
                    class="d-block text-center text-dark text-nowrap"
                    style="background: {{ $routePoint->pointObject->currentState()->color }};">
                {{ $routePoint->pointObject->getDisplayNumber() }}
            </a>

            @break
            @case(\App\ProductReturn::class)

            <a
                    href="{{ route('product-returns.edit', $routePoint->pointObject->id) }}"
                    target="_blank"
                    class="d-block text-center text-dark text-nowrap"
                    style="background: {{ $routePoint->pointObject->currentState()->color }};">
                {{ $routePoint->pointObject->id }}
            </a>

            @break
            @case(\App\ProductExchange::class)

            <a
                    href="{{ route('product-exchanges.edit', $routePoint->pointObject->id) }}"
                    target="_blank"
                    class="d-block text-center text-dark text-nowrap"
                    style="background: {{ $routePoint->pointObject->currentState()->color }};">
                {{ $routePoint->pointObject->id }}
            </a>

            @break
            @case(\App\CourierTask::class)
            <a
                    href="{{ route('courier-tasks.edit', $routePoint->pointObject->id) }}"
                    target="_blank"
                    class="d-block text-center text-dark text-nowrap"
                    style="background: wheat">
                {{ $routePoint->pointObject->id }}
            </a>
            @break
            @default

            {{ $routePoint->pointObject->id }}

            @break
        @endswitch
    </td>
            <td class="align-middle">
                @switch($routePoint->point_object_type)
                    @case(\App\Order::class)
                    {{$routePoint->pointObject->order_number}}

                    @break
                    @case(\App\ProductReturn::class)
                    @case(\App\ProductExchange::class)
                    {{$routePoint->pointObject->order->order_number}}
                    @break
                @endswitch
            </td>
            <td class="text-nowrap">
                    @php
                        $nextStates = $routePoint
                                        ->pointObject
                                        ->nextStates();
                        if($routePoint->point_object_type == \App\Order::class) {
                            $roleOrderStates = \Auth::user()->getOrderStateIdsByRole();
                            $nextStates = $nextStates->filter(function (\App\OrderState $state) use ($roleOrderStates){
                                return in_array($state->id, $roleOrderStates);
                            });
                        }
                        $nextStates = $nextStates->prepend($routePoint->pointObject->currentState())->pluck('name', 'id');
                    @endphp
                    {!! Form::select(
                            "point_object_state[{$routePoint->pointObject->id}]",
                            $nextStates,
                            $routePoint->pointObject->currentState()->id,
                            [
                                'class' => 'form-control form-control-sm w-auto w-min-100',
                                'data-action' => 'ajax-route-state-select',
                                'data-select-container' => 'route-point-row',
                                'data-url' => route('route-lists.actionRoutePoint', ['routePoint' => $routePoint->id, 'pointObjectState' => '_state_', 'store' => '_store_']),
                                'disabled' => $pay ? false : true
                            ]
                ) !!}
            </td>
    @if($pay)
        @if($routePoint->point_object_type == \App\CourierTask::class)
            <td></td>
            @else
        <td class="text-nowrap" data-js-group="checked-unchecked">
            <div class="d-inline-block">{!! Form::checkbox("point_cashboxes[{$routePoint->id}][is_own_cashbox]", 1, null) !!}</div>
            <div class="d-inline-block">{!! Form::select("point_cashboxes[{$routePoint->id}][cashbox_id]", $cashboxes, null, ['class' => 'form-control form-control-sm w-auto w-min-100']) !!}</div>
        </td>
            @endif
    @endif
        <td class="text-nowrap text-center" data-js-group="checked-unchecked">
            @if($routePoint->point_object_type == \App\CourierTask::class)
                {!! $routePoint->pointObject->date; !!}
            @else
                {!! $routePoint->pointObject->date_estimated_delivery; !!}
            @endif
        </td>

    @if(!$pay)
        <td class="text-center">
            {!! Form::checkbox('finance[]',  $routePoint->id , false, ['class' => 'pay-order']) !!}
        </td>
    @endif
</tr>
{!! Form::hidden("point_objects[{$routePoint->id}]", $routePoint->point_object_type , null, ['class' => 'form-control form-control-sm w-auto w-min-100']) !!}
{!! Form::hidden("order_id[{$routePoint->pointObject->id}]", $routePoint->point_object_type , null, ['class' => 'form-control form-control-sm w-auto w-min-100']) !!}
@switch($routePoint->point_object_type)
    @case(\App\Order::class)
    @case(\App\ProductReturn::class)
    @case(\App\ProductExchange::class)
    @if($routePoint->pointObject->orderDetails->isNotEmpty())
        <tr>
            <td colspan="8">
                <table class="table table-bordered mb-0">
                    @foreach($routePoint->pointObject->orderDetails as $orderDetail)
                        <tr>
                            <td class="w-25 align-middle">{{ $orderDetail->product->name }}</td>
                            <td class="w-25 text-nowrap align-middle">
                                @php
                                    $nextStates = $orderDetail->nextStates()->where('is_courier_state',1)->where('is_hidden','=',0)->where('owner_type', $routePoint->point_object_type)->get()->prepend($orderDetail->currentState())->pluck('name', 'id');
                                @endphp
                                {!! Form::select(
                                            "order_detail_state[{$orderDetail->id}]",
                                            $nextStates,
                                            $orderDetail->currentState()->id,
                                            [
                                                'class' => 'form-control form-control-sm w-auto w-min-100',
                                                'data-action' => 'ajax-route-state-select',
                                                'data-select-container' => 'route-point-row',
                                                'data-url' => route('route-lists.action', ['orderDetail' => $orderDetail->id, 'orderDetailState' => '_state_']),
                                                'disabled' => $pay ? false : true
                                            ]
                                ) !!}
                            </td>
                            <td class="w-auto text-nowrap align-middle">{{ $orderDetail->currency->name }}: <span
                                        class="font-weight-bold">{{ $orderDetail->price }}</span></td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    @endif
    @break
    @case(\App\CourierTask::class)
    <tr>
        <td colspan="8">
        {!! $routePoint->pointObject->comment !!}
        </td>
    </tr>
    @break
@endswitch
@switch($routePoint->point_object_type)
    @case(\App\ProductExchange::class)

    @if($routePoint->pointObject->exchangeOrderDetails->isNotEmpty())
        <tr>
            <td colspan="7">
                <table class="table table-bordered mb-0">
                    @foreach($routePoint->pointObject->exchangeOrderDetails as $orderDetail)
                        <tr>
                            <td class="w-25 align-middle">{{ $orderDetail->product->name }}</td>
                            <td class="w-25 text-nowrap align-middle">
                                @php
                                    $nextStates = $orderDetail->nextStates()->where('is_courier_state',0)->where('is_hidden','=',0)->where('owner_type', $routePoint->point_object_type)->get()->prepend($orderDetail->currentState())->pluck('name', 'id');
                                @endphp
                                {!! Form::select(
                                            "order_detail_state[{$orderDetail->id}]",
                                            $nextStates,
                                            $orderDetail->currentState()->id,
                                            [
                                                'class' => 'form-control form-control-sm w-auto w-min-100',
                                                'data-action' => 'ajax-route-state-select',
                                                'data-select-container' => 'route-point-row',
                                                'data-url' => route('route-lists.action', ['orderDetail' => $orderDetail->id, 'orderDetailState' => '_state_']),
                                                'disabled' => $pay ? false : true
                                            ]
                                ) !!}
                            </td>
                            <td class="w-auto text-nowrap align-middle">{{ $orderDetail->currency->name }}: <span
                                        class="font-weight-bold">{{ $orderDetail->price }}</span></td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>

    @endif

    @break
@endswitch
</tbody>