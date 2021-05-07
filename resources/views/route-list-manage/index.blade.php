@extends('layouts.app')

@section('scripts')
    @can('order-list')
        <style>
            .placemark_layout_container {
                position: relative;
                font-size: 10px;
                text-align: center;
                font-weight: bold;
            }

            /* Макет метки с "хвостиком" */
            .polygon_layout {
                background: #ffffff;
                border: 2px solid #943A43;
                border-radius: 5px;
                min-width: 26px;
                width: auto;
                height: 42px;
                position: absolute;
                left: -5px;
                top: -52px;
                color: #943A43;
            }

            .polygon_layout:after, .polygon_layout:before {
                top: 100%;
                left: 10px;
                border: solid transparent;
                content: " ";
                height: 0;
                width: 0;
                position: absolute;
            }

            .polygon_layout:after {
                border-top-color: #943A43;
                border-width: 10px;
                margin-left: -10px;
            }

            .polygon_layout:before {
                border-top-color: #943A43;
                border-width: 10px;
                margin-left: -10px;
            }
        </style>
        <script src="https://api-maps.yandex.ru/2.1/?apikey=d453a287-9ab0-4f30-b8fd-3fb0648b2b61&lang=ru_RU"></script>
        <script>
            var globalMap;
            (function () {
                ymaps.ready(initMap);

                function initMap() {
                    
                    var myMap,
                        myCollection = new ymaps.GeoObjectCollection(),
                        myCSRF = '{!! csrf_token() !!}',
                            myOrders = {
                            @foreach ($orders as $orderGroup)
                                    @foreach ($orderGroup as $order)
                            '{{ $order->getOrderNumber() }}': {
                                address: '{!! preg_replace('/\r\n|\r|\n/u', '',$order->getMapDeliveryAddress()) !!}',
                                geoCodeRoute: '{!! route('api.map-geo-code.add', $order->mapGeoCode) !!}',
                                geoCodeX: {!! $order->mapGeoCode->geoX ?? 'null' !!},
                                geoCodeY: {!! $order->mapGeoCode->geoY ?? 'null' !!},
                                properties: {
                                    iconContent: '{{ str_replace(':00','',$order->delivery_start_time) }}-{{ str_replace(':00','',$order->delivery_end_time) }}',
                                    balloonContent: '<b>{{ $order->getOrderNumber() }}</b><br>{!! str_replace("'", "\'", preg_replace("/\r\n|\r|\n/u", '', $order->getFullDeliveryAddress())) !!}<br><b>{{ $order->customer->first_name }} {{ $order->customer->last_name }}</b>{{ $order->customer->phone }}<br><b>{{ __('Products')}}</b> @foreach($order->orderDetails as $orderDetail) <br> {{$orderDetail->product->reference}} - {{$orderDetail->price}} @endforeach',
                                    number: 'З-{{ $order->order_number }}',
                                    color: '{{ is_null($order->routeList()) ? 'gray' : $order->routeList()->courier->color }}'
                                }
                            },
                            @endforeach
                                    @endforeach
                                    @foreach ($productReturns as $productReturnGroup)
                                    @foreach ($productReturnGroup as $productReturn)
                            'В-{{ $productReturn->id }}': {
                                address: '{!! preg_replace('/\r\n|\r|\n/u', '',$productReturn->getMapDeliveryAddress()) !!}',
                                geoCodeRoute: '{!! route('api.map-geo-code.add', $productReturn->mapGeoCode) !!}',
                                geoCodeX: {!! $productReturn->mapGeoCode->geoX ?? 'null' !!},
                                geoCodeY: {!! $productReturn->mapGeoCode->geoY ?? 'null' !!},
                                properties: {
                                    iconContent: '{{ str_replace(':00','',$productReturn->delivery_start_time) }}-{{ str_replace(':00','',$productReturn->delivery_end_time) }}',
                                    balloonContent: '<b>{{ $productReturn->id }}</b><br>{!! str_replace("'", "\'", preg_replace("/\r\n|\r|\n/u", '', $productReturn->getFullAddress())) !!}<br><b>{{ $productReturn->order->customer->first_name }} {{ $productReturn->order->customer->last_name }}</b>{{ $productReturn->order->customer->phone }}<br><b>{{ __('Products')}}</b> @foreach($productReturn->orderDetails as $orderDetail) <br> {{$orderDetail->product->reference}} - {{$orderDetail->price}} @endforeach',
                                    number: 'В-{{ $productReturn->id }}',
                                    color: '{{ is_null($productReturn->routeList()) ? 'gray' : $productReturn->routeList()->courier->color }}'
                                }
                            },
                            @endforeach
                                    @endforeach
                                    @foreach ($productExchanges as $productExchangeGroup)
                                    @foreach ($productExchangeGroup as $productExchange)
                            'О-{{ $productExchange->id }}': {
                                address: '{!! preg_replace('/\r\n|\r|\n/u', '',$productExchange->getMapDeliveryAddress()) !!}',
                                geoCodeRoute: '{!! route('api.map-geo-code.add', $productExchange->mapGeoCode) !!}',
                                geoCodeX: {!! $productExchange->mapGeoCode->geoX ?? 'null' !!},
                                geoCodeY: {!! $productExchange->mapGeoCode->geoY ?? 'null' !!},
                                properties: {
                                    iconContent: '{{ str_replace(':00','',$productExchange->delivery_start_time) }}-{{ str_replace(':00','',$productExchange->delivery_end_time) }}',
                                    balloonContent: '<b>{{ $productExchange->id }}</b><br>{!! str_replace("'", "\'", preg_replace("/\r\n|\r|\n/u", '', $productExchange->getFullAddress())) !!}<br><b>{{ $productExchange->order->customer->first_name }} {{ $productExchange->order->customer->last_name }}</b>{{ $productExchange->order->customer->phone }}<br><b>{{ __('Products')}}</b> @foreach($productExchange->orderDetails as $orderDetail) <br> {{$orderDetail->product->reference}} - {{$orderDetail->price}} @endforeach <br><b>{{ __('Exchange Order Details')}}</b> @foreach($productExchange->exchangeOrderDetails as $orderDetail) <br> {{$orderDetail->product->reference}} - {{$orderDetail->price}} @endforeach',
                                    number: 'О-{{ $productExchange->id }}',
                                    color: '{{ is_null($productExchange->routeList()) ? 'gray' : $productExchange->routeList()->courier->color }}'
                                }
                            },
                            @endforeach
                            @endforeach
                                @foreach($courierTasks as $courierTaskGroup)
                                    @foreach($courierTaskGroup as $courierTask)
                                    'K-{{ $courierTask->id }}': {
                                    address: '{!! preg_replace('/\r\n|\r|\n/u', '',$courierTask->getMapDeliveryAddress()) !!}',
                                    geoCodeRoute: '{!! route('api.map-geo-code.add', $courierTask->mapGeoCode) !!}',
                                    geoCodeX: {!! $courierTask->mapGeoCode->geoX ?? 'null' !!},
                                    geoCodeY: {!! $courierTask->mapGeoCode->geoY ?? 'null' !!},
                                    properties: {
                                        iconContent: '{{ str_replace(':00','',$courierTask->start_time) }}-{{ str_replace(':00','',$courierTask->end_time) }}',
                                        balloonContent: '<b>{{ $courierTask->id }}</b><br>{!! str_replace("'", "\'", preg_replace("/\r\n|\r|\n/u", '', $courierTask->getMapDeliveryAddress())) !!}<br><b>{!! str_replace("'", "\'", preg_replace("/\r\n|\r|\n/u", '', $courierTask->comment)) !!}</b>',
                                        number: 'K-{{ $courierTask->id }}',
                                        color: '{{ is_null($courierTask->routeList()) ? 'gray' : $courierTask->routeList()->courier->color }}'
                                    }
                            },
                                    @endforeach
                                @endforeach
                        },
                        getPoints = 0,
                        polygonLayout = ymaps.templateLayoutFactory.createClass('<div class="placemark_layout_container"><div class="polygon_layout text-nowrap"><div class="text-dark p-1" style="background-color: @{{ properties.color }}">@{{ properties.number }}</div><div class="p-1">@{{ properties.iconContent }}</div></div></div>');

                    var mapElement = document.getElementById('map');
                    mapElement.style.height = mapElement.offsetWidth + 'px';
                    myMap = new ymaps.Map("map", {
                        center: [{{$city->x_coordinate}}, {{$city->y_coordinate}}],
                        zoom: 11
                    });
                    globalMap = myMap;
                    var toRefresh = false;

                    Object.keys(myOrders).forEach(function (order) {
                        if (myOrders[order].geoCodeX === null) {
                            ymaps.geocode(myOrders[order].address).then(function (res) {
                                var coordinates = res.geoObjects.get(0).geometry.getCoordinates();
                                addPlacemark(coordinates, myOrders[order].properties);

                                refreshMap();

                                var data = {
                                    'geoX': coordinates[0],
                                    'geoY': coordinates[1]
                                };

                                $.ajax({
                                    url : myOrders[order].geoCodeRoute,
                                    type: 'POST',
                                    dataType : "json",
                                    data: data,
                                    beforeSend: function(xhr){xhr.setRequestHeader('X-CSRF-TOKEN', myCSRF);},
                                });

                            });

                        } else {
                            addPlacemark([myOrders[order].geoCodeX, myOrders[order].geoCodeY], myOrders[order].properties);
                            toRefresh = true;
                        }


                        function addPlacemark(coordinates, properties) {
                            var myPlacemark = new ymaps.Placemark(coordinates, properties, {
                                iconLayout: polygonLayout,
                                iconShape: {
                                    type: 'Polygon',
                                    coordinates: [
                                        [[-5, -52], [-5, -10], [21, -10], [21, -52], [-5, -52]]
                                    ]
                                }
                            });
                            myCollection.add(myPlacemark);
                        }
                    });

                    if (toRefresh) {
                        refreshMap();
                    }

                    window.myMap = {
                        'Map': myMap,
                        'Orders': myOrders
                    };


                    function refreshMap() {
                        getPoints++;
                        if (getPoints = Object.keys(myOrders).length) {
                            myMap.geoObjects.add(myCollection);
                            myMap.setBounds(myCollection.getBounds(), {checkZoomRange: true});
                        }
                    }

                    $('[data-map-place-mark]').change(function () {
                        let $select = $(this);
                        let color = $select.find('option:selected').data('color');
                        let placeMarkId = $select.data('map-place-mark');

                        $select.closest('tr').css('background-color', color);

                        window.myMap.Map.geoObjects.each(function (el, i) {
                            el.each(function (el, i) {
                                if (el.properties.get('number') == placeMarkId) {
                                    el.properties.set('color', color);
                                }
                            });
                        });
                    });
                }
            })();
        </script>
    @endcan
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif
    @can('order-list')
        @if (count($errors) > 0)
            <div class="alert alert-danger">
                <strong>{{ __('Whoops!') }}</strong> {{ __('There were some problems with your input.') }}<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="col-8" style="width: 400px; display: flex">
            {!! Form::text('delivery_city', null, ['class' => 'form-control', 'id' => 'city']) !!}
            {!! Form::button(('Поиск'), ['type' => 'button', 'class' => 'btn btn-sm btn-primary mb-2 ml-2', 'id' => 'sputnik-search']) !!}
        </div>
        {!! Form::open(['route' => 'route-list-manage.index', 'method' => 'GET', 'class' => 'form-inline pull-right']) !!}
        <div class="form-group mb-2" id="deliveryDate">
            {!! Form::label('date_estimated_delivery', __('Date').':') !!}
            {!! Form::text('date_estimated_delivery', $date, ['class' => 'form-control form-control-sm date ml-2', 'size' => 6, 'autocomplete' => 'off']) !!}
        </div>
        <div class="form-group ml-3 mb-2">
            {!! Form::label('city_id', __('City').':') !!}
            {!! Form::select('city_id', \App\City::pluck('name', 'id'), $city->id, ['class' => 'form-control form-control-sm ml-2']) !!}
        </div>
        {!! Form::button(__('Submit'), ['type' => 'submit', 'class' => 'btn btn-sm btn-primary mb-2 ml-2']) !!}
        {!! Form::close() !!}
        <div class="row pull-left w-100">
            <div id="map" class=" col-6 p-0 m-0 w-100"></div>
            <div class="col-6">
                {!! Form::open(['route' => 'route-list-manage.update', 'method' => 'POST', 'class' => 'form-inline pull-right']) !!}
                {!! Form::hidden('date', $date) !!}
                {!! Form::hidden('city_id', $city->id) !!}
                <table class="table table-sm small">
                    <thead>
                    <tr>
                        <th class="text-nowrap">{{ __('Route List') }}</th>
                        <th>{{ __('Order') }}</th>
                        <th>{{ __('Address') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Time') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($orders as $routeListId => $orderGroup)
                        @foreach ($orderGroup as $order)
                            <tr @if(!is_null($order->routeList())) style="background-color: {{ $order->routeList()->courier->color }}" @endif>
                                <td>{!! Form::select("orders_route_lists[{$order->id}]", $routeLists, "RL-{$routeListId}", ['class' => 'form-control  form-control-sm', 'data-map-place-mark' => "З-{$order->order_number}"], $routeListsColor->prepend(['data-color' => 'gray'], 'RL-0')->toArray()) !!}</td>
                                <td>
                                    <a class="text-dark" href="{{route('orders.edit',['id'=>$order->id])}}">{{ $order->getOrderNumber() }}</a>
                                    <br>
                                    {{$order->currentState()->name}}</td>
                                <td>{{ $order->getFullDeliveryAddress() }}</td>
                                <td>{{ $order->customer->first_name }} {{ $order->customer->last_name }} {{ $order->customer->phone }}</td>
                                <td class="text-nowrap">{{ $order->delivery_start_time }}
                                    - {{ $order->delivery_end_time }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
                <table class="table table-sm small">
                    <thead>
                    <tr>
                        <th class="text-nowrap">{{ __('Route List') }}</th>
                        <th>{{ __('Return') }}</th>
                        <th>{{ __('Address') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Time') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($productReturns as $routeListId => $productReturnGroup)
                        @foreach ($productReturnGroup as $productReturn)
                            <tr @if(!is_null($productReturn->routeList())) style="background-color: {{ $productReturn->routeList()->courier->color }}" @endif>
                                <td>{!! Form::select("product_returns_route_lists[{$productReturn->id}]", $routeLists, "RL-{$routeListId}", ['class' => 'form-control  form-control-sm', 'data-map-place-mark' => "В-{$productReturn->id}"], $routeListsColor->prepend(['data-color' => 'gray'], 'RL-0')->toArray()) !!}</td>
                                <td>
                                    <a class="text-dark"
                                       href="{{route('product-returns.edit',['id'=>$productReturn->id])}}">{{ $productReturn->id }}</a>
                                    <br>
                                    {{$productReturn->currentState()->name}}</td>
                                <td>{{ $productReturn->getFullAddress() }}</td>
                                <td>{{ $productReturn->order->customer->first_name }} {{ $productReturn->order->customer->last_name }} {{ $productReturn->order->customer->phone }}</td>
                                <td class="text-nowrap">{{ $productReturn->delivery_start_time }}
                                    - {{ $productReturn->delivery_end_time }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
                <table class="table table-sm small">
                    <thead>
                    <tr>
                        <th class="text-nowrap">{{ __('Route List') }}</th>
                        <th>{{ __('Exchange') }}</th>
                        <th>{{ __('Address') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Time') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($productExchanges as $routeListId => $productExchangeGroup)
                        @foreach ($productExchangeGroup as $productExchange)
                            <tr @if(!is_null($productExchange->routeList())) style="background-color: {{ $productExchange->routeList()->courier->color }}" @endif>
                                <td>{!! Form::select("product_exchanges_route_lists[{$productExchange->id}]", $routeLists, "RL-{$routeListId}", ['class' => 'form-control  form-control-sm', 'data-map-place-mark' => "О-{$productExchange->id}"], $routeListsColor->prepend(['data-color' => 'gray'], 'RL-0')->toArray()) !!}</td>
                                <td>
                                    <a class="text-dark"
                                       href="{{route('product-exchanges.edit',['id'=>$productExchange->id])}}">{{ $productExchange->id }}</a>
                                    <br>
                                    {{$productExchange->currentState()->name}}</td>
                                <td>{{ $productExchange->getFullAddress() }}</td>
                                <td>{{ $productExchange->order->customer->first_name }} {{ $productExchange->order->customer->last_name }} {{ $productExchange->order->customer->phone }}</td>
                                <td class="text-nowrap">{{ $productExchange->delivery_start_time }}
                                    - {{ $productExchange->delivery_end_time }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
                <table class="table table-sm small">
                    <thead>
                    <tr>
                        <th class="text-nowrap">{{ __('Route List') }}</th>
                        <th>{{ __('Courier tasks') }}</th>
                        <th>{{ __('Address') }}</th>
                        <th>{{ __('Comment') }}</th>
                        <th>{{ __('Time') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($courierTasks as $routeListId => $courierTaskGroup)
                        @foreach ($courierTaskGroup as $courierTask)
                            <tr @if(!is_null($courierTask->routeList())) style="background-color: {{ $courierTask->routeList()->courier->color }}" @endif>
                                <td>{!! Form::select("courier_tasks_route_lists[{$courierTask->id}]", $routeLists, "RL-{$routeListId}", ['class' => 'form-control  form-control-sm', 'data-map-place-mark' => "K-{$courierTask->id}"], $routeListsColor->prepend(['data-color' => 'gray'], 'RL-0')->toArray()) !!}</td>
                                <td>{{ $courierTask->id }}</td>
                                <td>{{ $courierTask->getMapDeliveryAddress() }}</td>
                                <td>{{$courierTask->comment}}</td>
                                <td class="text-nowrap">{{ $courierTask->start_time }}
                                    - {{ $courierTask->end_time }}</td>
                            </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
                {!! Form::button(__('Submit'), ['type' => 'submit', 'class' => 'btn btn-sm btn-primary mb-2 ml-2']) !!}
                {!! Form::close() !!}
            </div>
        </div>
        <div class="clearfix"></div>
    @endcan
    <div class="col-4">
    <table class="table table-sm small">
        <thead>
        <tr>
            <th class="text-nowrap">{{__('Route List')}}</th>
            <th class="text-nowrap">{{__('Unprocessed route points (Order, Exchange)')}}</th>
            <th class="text-nowrap">{{__('Courier left')}}</th>
        </tr>
        </thead>
        <tbody>
        @foreach(\App\RouteList::all()->filter(function (\App\RouteList $routeList){return !$routeList->courier->is_not_working;}) as $routeList)
            <tr>
                <td>{{$routeList->courier->name}}</td>
                <td>{{ $routeList->getCountTodayRoutePoints() }}</td>
                {!! Form::open(['route' => ['courier-left', $routeList->id],'method'=>'POST']) !!}
                <td>{!! Form::submit(__('Courier left'), ['class' => (bool)$routeList->getCountTodayRoutePoints() == 0 ? 'btn btn-sm btn-secondary' : 'btn btn-sm btn-success', 'name' => 'submit', 'disabled' => (bool)$routeList->getCountTodayRoutePoints() == 0]) !!}</td>
                {!! Form::close() !!}
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
@endsection
