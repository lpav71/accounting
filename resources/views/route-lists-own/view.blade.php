@extends('layouts.app')

@section('content')
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
    <div>
        <h5>{{ __('Route List') }} № {{ $routeList->id }}</h5>
        <h6>{{ __('Courier') }}: {{ Auth::getUser()->name }}</h6>
    </div>
    <div>
        {{__('Unprocessed route points (Order, Exchange)')}} : {{$routeList->getCountTodayRoutePoints()}}
    </div>
    {!! Form::open(['route' => ['courier-left', $routeList->id],'method'=>'POST']) !!}
    <td>{!! Form::submit(__('Took orders for today'), ['class' => (bool)$routeList->getCountTodayRoutePoints() == 0 ? 'btn btn-sm btn-secondary' : 'btn btn-sm btn-success', 'name' => 'submit', 'disabled' => (bool)$routeList->getCountTodayRoutePoints() == 0]) !!}</td>
    {!! Form::close() !!}
    {!! Form::open(['method'=>'GET']) !!}
    <div class="form-group col-xs-12 col-sm-2" id="deliveryDate">
        {!! Form::label('date_list', __('Date:')) !!}
        @php
            use Illuminate\Support\Carbon;
        @endphp
        {!! Form::text('date_list',$date, ['class' => 'form-control date', 'autocomplete' => 'off']) !!}
    </div>
    <div class="form-group col-xs-12 col-sm-2">
    {{ Form::select('pointState', $pointStates , Request::input('pointState'), ['class' => 'form-control form-control-sm selectpicker']) }}
    </div>
    <div class="form-group col-xs-12 col-sm-2">
        {!! Form::submit(__('Find'), ['class' => 'btn btn-primary']) !!}
    </div>

    {!! Form::close() !!}
    @foreach ($routeList->routePoints->sortByDesc('point_object_id')->groupBy('point_object_type') as $groupName => $routePointGroup)
        <div class="p-1 bg-primary text-light">
            @switch($groupName)
                @case(\App\Order::class)

                {{ __('Orders') }}

                @break
                @case(\App\ProductReturn::class)

                {{ __('Returns') }}

                @break
                @case(\App\ProductExchange::class)

                {{ __('Exchanges') }}

                @break
                @case(\App\CourierTask::class)
                {{__('Courier task')}}
                @break
                @default

                {{ __('Points') }}

                @break
            @endswitch
        </div>
        @foreach($routePointGroup as $routePoint)

            @include('route-lists-own._partials.route-point.row', ['routePoint' => $routePoint])

        @endforeach
    @endforeach
@endsection