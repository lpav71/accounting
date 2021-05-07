@extends('layouts.app')

@section('scripts')
{{--    @can('order-list')--}}
{{--        <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU"></script>--}}
{{--        <script>--}}
{{--            (function () {--}}
{{--                ymaps.ready(initMap);--}}

{{--                function initMap() {--}}
{{--                    var myMap,--}}
{{--                        myCollection = new ymaps.GeoObjectCollection(),--}}
{{--                        myOrders = {--}}
{{--                            @foreach ($orders as $order)--}}
{{--                            '{{ $order->getDisplayNumber() }}': {--}}
{{--                                address: '{!! preg_replace('/\r\n|\r|\n/u', '',$order->getMapDeliveryAddress()) !!}',--}}
{{--                                properties: {--}}
{{--                                    iconContent: '{{ str_replace(':00','',$order->delivery_start_time) }}-{{ str_replace(':00','',$order->delivery_end_time) }}',--}}
{{--                                    balloonContent: '<b>{{ $order->getDisplayNumber() }}</b><br>{!! str_replace("'", "\'", preg_replace("/\r\n|\r|\n/u", '', $order->getFullDeliveryAddress())) !!}<br><b>{{ $order->customer->first_name }} {{ $order->customer->last_name }}</b>{{ $order->customer->phone }}'--}}
{{--                                }--}}
{{--                            },--}}
{{--                            @endforeach--}}
{{--                        },--}}
{{--                        getPoints = 0;--}}

{{--                    var mapElement = document.getElementById('map');--}}
{{--                    mapElement.style.height = mapElement.offsetWidth + 'px';--}}

{{--                    myMap = new ymaps.Map("map", {--}}
{{--                        center: [55.76, 37.64],--}}
{{--                        zoom: 11--}}
{{--                    });--}}

{{--                    Object.keys(myOrders).forEach(function (order) {--}}
{{--                        ymaps.geocode(myOrders[order].address).then(function (res) {--}}
{{--                            var myPlacemark = new ymaps.Placemark(res.geoObjects.get(0).geometry.getCoordinates(), myOrders[order].properties, {preset: 'islands#darkGreenStretchyIcon'});--}}
{{--                            myCollection.add(myPlacemark);--}}
{{--                            refreshMap();--}}
{{--                        });--}}
{{--                    });--}}


{{--                    function refreshMap() {--}}
{{--                        getPoints++;--}}
{{--                        if (getPoints = Object.keys(myOrders).length) {--}}
{{--                            myMap.geoObjects.add(myCollection);--}}
{{--                            myMap.setBounds(myCollection.getBounds(), {checkZoomRange: true});--}}
{{--                        }--}}
{{--                    }--}}
{{--                }--}}
{{--            })();--}}
{{--        </script>--}}
{{--    @endcan--}}
@endsection

@section('content')
{{--    @if (session('status'))--}}
{{--        <div class="alert alert-success">--}}
{{--            {{ session('status') }}--}}
{{--        </div>--}}
{{--    @endif--}}
{{--    @can('order-list')--}}
{{--        {!! Form::open(['route' => 'home', 'method' => 'GET', 'class' => 'form-inline pull-right']) !!}--}}
{{--        <div class="form-group mb-2" id="deliveryDate">--}}
{{--            {!! Form::label('date_estimated_delivery', __('Estimated delivery Date:')) !!}--}}
{{--            {!! Form::text('date_estimated_delivery', $date, ['class' => 'form-control form-control-sm date ml-2', 'size' => 6]) !!}--}}
{{--        </div>--}}
{{--        <div class="form-group mb-2" style="min-width: 200px;">--}}
{{--            {!! Form::select('carriers_id[]', $availableCarriers, Request::input('carriers_id'), ['multiple' => true, 'class' => 'form-control form-control-sm ml-2 selectpicker']) !!}--}}
{{--        </div>--}}
{{--        <div class="form-group mb-2" style="min-width: 200px;">--}}
{{--            {!! Form::select('order_states_id[]', $orderStates, Request::input('order_states_id'), ['multiple' => true, 'class' => 'form-control form-control-sm ml-2 selectpicker']) !!}--}}
{{--        </div>--}}
{{--        {!! Form::button(__('Submit'), ['type' => 'submit', 'class' => 'btn btn-sm btn-primary mb-2 ml-2']) !!}--}}
{{--        {!! Form::close() !!}--}}
{{--        <div class="row pull-left w-100">--}}
{{--            <div id="map" class=" col-6 p-0 m-0 w-100"></div>--}}
{{--            <div class="col-6">--}}
{{--                <table class="table table-sm small">--}}
{{--                    <thead>--}}
{{--                    <tr>--}}
{{--                        <th>{{ __('Order') }}</th>--}}
{{--                        <th>{{ __('Address') }}</th>--}}
{{--                        <th>{{ __('Customer') }}</th>--}}
{{--                        <th>{{ __('Time') }}</th>--}}
{{--                    </tr>--}}
{{--                    </thead>--}}
{{--                    <tbody>--}}
{{--                    @foreach ($orders as $order)--}}
{{--                        <tr>--}}
{{--                            <td>{{ $order->getDisplayNumber() }}</td>--}}
{{--                            <td>{{ $order->getFullDeliveryAddress() }}</td>--}}
{{--                            <td>{{ $order->customer->first_name }} {{ $order->customer->last_name }} {{ $order->customer->phone }}</td>--}}
{{--                            <td>{{ str_replace(':00','',$order->delivery_start_time) }}-{{ str_replace(':00','',$order->delivery_end_time) }}</td>--}}
{{--                        </tr>--}}
{{--                    @endforeach--}}
{{--                    </tbody>--}}
{{--                </table>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="clearfix"></div>--}}
{{--    @endcan--}}
@endsection
