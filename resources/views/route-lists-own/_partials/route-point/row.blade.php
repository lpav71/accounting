<div class="border rounded-bottom mt-1" data-container="route-point-row">
    <div class="d-flex bg-info p-1">
        <span class="mt-auto mb-auto w-100 small">
            @switch($routePoint->point_object_type)
                @case(\App\Order::class)

                {{ $routePoint->pointObject->getDisplayNumber() }} | {{ $routePoint->pointObject->currentState()->name }} | {{ $routePoint->pointObject->order_number }} {{ $routePoint->pointObject->delivery_start_time }} - {{ $routePoint->pointObject->delivery_end_time }} {{ $routePoint->pointObject->date_estimated_delivery }}

                @break
                @default

                {{ $routePoint->pointObject->id }}
                {{ $routePoint->pointObject->delivery_estimated_date }}

                @break
                @case(\App\CourierTask::class)
                {{ $routePoint->pointObject->start_time }} - {{ $routePoint->pointObject->end_time }} {{ $routePoint->pointObject->date }}
            @endswitch
        </span>
        <span class="text-nowrap p-1 mr-2 text-capitalize text-light">
            @switch($routePoint->point_object_type)
                @case(\App\Order::class)

                {{ $routePoint->pointObject->channel->name }}

                @break
                @case(\App\ProductReturn::class)
                @case(\App\ProductExchange::class)

                {{ $routePoint->pointObject->order->channel->name }}

                @break
            @endswitch
        </span>
        <a href="yandexmaps://maps.yandex.ru/?text={{ $routePoint->getMapDeliveryAddress() }}"
           class="btn btn-sm btn-primary mt-auto mb-auto mr-2">
            <i class="fa fa-map-marker"></i>
        </a>
        <div class="dropdown mt-auto mb-auto mr-2">
                        <span id="pointAddress{{ $routePoint->id }}" class="btn btn-sm btn-primary"
                              data-toggle="dropdown"
                              aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-map-signs"></i>
                        </span>
            <div class="dropdown-menu dropdown-menu-right p-4 change-position-block"
                 aria-labelledby="pointAddress{{ $routePoint->id }}"
                 style="min-width: 300px;">
                {{ $routePoint->getFullAddress() }}
            </div>
        </div>
        @switch($routePoint->point_object_type)
            @case(\App\Order::class)

            <div class="dropdown mt-auto mb-auto mr-2">
                        <span id="pointCustomer{{ $routePoint->id }}" class="btn btn-sm btn-primary"
                              data-toggle="dropdown"
                              aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-user-o"></i>
                        </span>
                <div class="dropdown-menu dropdown-menu-right p-4"
                     aria-labelledby="pointCustomer{{ $routePoint->id }}">
                    <div>{{ $routePoint->pointObject->customer->getName() }}</div>
                    <div>{{ $routePoint->pointObject->customer->phone }}</div>
                </div>
            </div>
            <a href="tel:{{ $routePoint->pointObject->customer->phone }}"
               class="btn btn-sm btn-primary mt-auto mb-auto">
                <i class="fa fa-phone"></i>
            </a>

            @break
            @case(\App\ProductReturn::class)
            @case(\App\ProductExchange::class)

            <div class="dropdown mt-auto mb-auto mr-2">
                        <span id="pointCustomer{{ $routePoint->id }}" class="btn btn-sm btn-primary"
                              data-toggle="dropdown"
                              aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-user-o"></i>
                        </span>
                <div class="dropdown-menu dropdown-menu-right p-4"
                     aria-labelledby="pointCustomer{{ $routePoint->id }}">
                    <div>{{ $routePoint->pointObject->order->customer->getName() }}</div>
                    <div>{{ $routePoint->pointObject->order->customer->phone }}</div>
                </div>
            </div>
            <a href="tel:{{ $routePoint->pointObject->order->customer->phone }}"
               class="btn btn-sm btn-primary mt-auto mb-auto">
                <i class="fa fa-phone"></i>
            </a>

            @break
            @case(\App\CourierTask::class)
            <div class="dropdown mt-auto mb-auto mr-2">
                        <span id="pointCustomer{{ $routePoint->id }}" class="btn btn-sm btn-primary"
                              data-toggle="dropdown"
                              aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-comments-o"></i>
                        </span>
                <div class="dropdown-menu dropdown-menu-right p-4"
                     aria-labelledby="pointCustomer{{ $routePoint->id }}">
                    <div>{{ $routePoint->pointObject->comment }}</div>
                </div>
            </div>
            @break
        @endswitch
    </div>
    <div class="d-flex p-0" style="background-color: {{ $routePoint->currentState()->color }};">
        @php
            /**
            * @var \Illuminate\Support\Collection $nextStates
            */
            $nextStates = $routePoint
                            ->nextStates()
                            ->prepend($routePoint->currentState());

            $nextStatesAttributes = $nextStates->reduce(
                function($acc, \App\RoutePointState $routePointState) use ($routePoint) {
                    if ($routePointState->is_need_comment_to_point_object) {
                        $acc[$routePointState->id] = ['data-modal' => "rpModal{$routePoint->id}-{$routePointState->id}"];
                    }

                    return $acc;
                },
                []
            );

            $nextStates = $nextStates->pluck('name', 'id');

        @endphp
        {!! Form::select(
                    "point_state[{$routePoint->id}]",
                    $nextStates,
                    $routePoint->currentState()->id,
                    [
                        'class' => 'form-control form-control-plaintext w-auto w-min-100',
                        'data-action' => 'ajax-route-state-select',
                        'data-select-container' => 'route-point-row',
                        'data-url' => route('route-point-states.actionRoutePoint', ['routePoint' => $routePoint->id, 'routePointState' => '_state_'])
                    ],
                    $nextStatesAttributes
        ) !!}
        @foreach($nextStatesAttributes as $option)
            <!-- Modal -->
                <div class="modal fade" id="{{ $option['data-modal'] }}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">{{ __('Comment') }}</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                {!! Form::textarea('comment', null, ['class' => 'form-control']) !!}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-action="submit">{{ __('Save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
        @endforeach
    </div>
    @switch($routePoint->point_object_type)
        @case(\App\Order::class)
        @case(\App\ProductReturn::class)
        @case(\App\ProductExchange::class)
        @if($routePoint->pointObject->orderDetails->isNotEmpty())
            @foreach($routePoint->pointObject->orderDetails as $orderDetail)
                @php
                    $state = preg_replace('/\s+\(.+\)/','', $orderDetail->currentState()->name);
                @endphp
                <div class="d-flex p-1 bg-white @if(!$loop->last) border-bottom border-dark @endif"
                     data-container="order-detail">
                        <span class="mt-auto mb-auto w-100 pr-2">
                            {{ $orderDetail->product->name }}
                            <div class="small mb-auto mt-auto pr-2 text-black-50">{{ $orderDetail->price }}</div>
                        </span>

                    @php
                        $nextStates = $orderDetail->nextStates()->where('is_courier_state','=',1)->where('is_hidden','=',0)->where('owner_type', $routePoint->point_object_type)->pluck('name', 'id');
                    @endphp
                    <span class="p-2 mt-auto mb-auto font-weight-bold" data-container="order-detail-state">
                                    {{ $state }}
                        </span>
                    @include('route-lists-own.buttons.order-detail', ['nextStates' => $nextStates, 'orderDetail' => $orderDetail])
                </div>
            @endforeach
        @endif
        @break
        @case(\App\CourierTask::class)
        @php
            $state = preg_replace('/\s+\(.+\)/','', $routePoint->pointObject->currentState()->name);
        @endphp
        <div class="d-flex p-1 bg-white @if(!$loop->last) border-bottom border-dark @endif"
             data-container="order-detail">
                        <span class="mt-auto mb-auto w-100 pr-2">
                            {{ $routePoint->pointObject->comment }}
                        </span>
            @php
                $nextStates = $routePoint->pointObject->nextStates()->where('is_courier_state','=',1)->pluck('name', 'id');
            @endphp
            <span class="p-2 mt-auto mb-auto font-weight-bold" data-container="order-detail-state">
                                    {{ $state }}
                        </span>
            @include('route-lists-own.buttons.courier-task', ['nextStates' => $nextStates, 'courierTask' => $routePoint->pointObject])
        </div>
        @break
    @endswitch
    @switch($routePoint->point_object_type)
        @case(\App\ProductExchange::class)

        @if($routePoint->pointObject->exchangeOrderDetails->isNotEmpty() && $routePoint->is_point_object_attached)
            @foreach($routePoint->pointObject->exchangeOrderDetails as $orderDetail)
                @php
                    $state = preg_replace('/\s+\(.+\)/','', $orderDetail->currentState()->name);
                @endphp
                <div class="d-flex p-1 bg-white @if(!$loop->last) border-bottom border-dark @endif"
                     data-container="order-detail">
                        <span class="mt-auto mb-auto w-100 pr-2">
                            {{ $orderDetail->product->name }}
                            <div class="small mb-auto mt-auto pr-2 text-black-50">{{ $orderDetail->price }}</div>
                        </span>

                    @php
                        $nextStates = $orderDetail->nextStates()->where('is_courier_state','=',1)->where('is_hidden','=',0)->where('owner_type', $routePoint->point_object_type)->pluck('name', 'id');
                    @endphp
                    <span class="p-2 mt-auto mb-auto font-weight-bold" data-container="order-detail-state">
                                    {{ $state }}
                        </span>
                    @include('route-lists-own.buttons.order-detail', ['nextStates' => $nextStates, 'orderDetail' => $orderDetail])
                </div>
            @endforeach
        @endif

        @break
    @endswitch
</div>