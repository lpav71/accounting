@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Route List State') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('route-list-states.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
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
    {!! Form::open(['route' => 'route-list-states.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_editable_route_list', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_editable_route_list', __('Is editable Route List'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_deletable_route_points', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_deletable_route_points', __('Is deletable route points'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_create_currency_operations', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_create_currency_operations', __('Create Cashbox Operations'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('color', __('Color:')) !!}
                {!! Form::text('color', null, ['placeholder' => '#FFFFFF','class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('previous_states_id[]', __('Previous States:')) !!}
                {!! Form::select('previous_states_id[]', $routeListStates, [], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('need_order_detail_state_id[]', __('Need Order Detail states:')) !!}
                {!! Form::select('need_order_detail_state_id[]', $orderDetailStates, [], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="border rounded p-3 bg-light form-group">
                <h4>{{ __('New Order Detail states') }}:</h4>
                @foreach($orderDetailStates as $id => $orderDetailStateName)
                    <div class="form-group">
                        {!! Form::label("new_order_detail_states[{$id}]", $orderDetailStateName, ['class' => 'border-bottom d-block bg-info p-2']) !!}
                        {!! Form::select("new_order_detail_states[{$id}]", $orderDetailStates, $id, ['class' => 'form-control selectpicker']) !!}
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('need_order_state_id[]', __('Need Order states:')) !!}
                {!! Form::select('need_order_state_id[]', $orderStates, [], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="border rounded p-3 bg-light form-group">
                <h4>{{ __('New Order states') }}:</h4>
                @foreach($orderStates as $id => $orderStateName)
                    <div class="form-group">
                        {!! Form::label("new_order_states[{$id}]", $orderStateName, ['class' => 'border-bottom d-block bg-info p-2']) !!}
                        {!! Form::select("new_order_states[{$id}]", $orderStates, $id, ['class' => 'form-control selectpicker']) !!}
                    </div>
                @endforeach
            </div>
            <div class="form-group">
                {!! Form::label('need_product_return_state_id[]', __('Need Product Return states:')) !!}
                {!! Form::select('need_product_return_state_id[]', $productReturnStates, [], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="border rounded p-3 bg-light form-group">
                <h4>{{ __('New Product Return states') }}:</h4>
                @foreach($productReturnStates as $id => $productReturnStateName)
                    <div class="form-group">
                        {!! Form::label("new_product_return_states[{$id}]", $productReturnStateName, ['class' => 'border-bottom d-block bg-info p-2']) !!}
                        {!! Form::select("new_product_return_states[{$id}]", $productReturnStates, $id, ['class' => 'form-control selectpicker']) !!}
                    </div>
                @endforeach
            </div>
            <div class="form-group">
                {!! Form::label('need_product_exchange_state_id[]', __('Need Product Exchange states:')) !!}
                {!! Form::select('need_product_exchange_state_id[]', $productExchangeStates, [], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="border rounded p-3 bg-light form-group">
                <h4>{{ __('New Product Exchange states') }}:</h4>
                @foreach($productExchangeStates as $id => $productExchangeStateName)
                    <div class="form-group">
                        {!! Form::label("new_product_exchange_states[{$id}]", $productExchangeStateName, ['class' => 'border-bottom d-block bg-info p-2']) !!}
                        {!! Form::select("new_product_exchange_states[{$id}]", $productExchangeStates, $id, ['class' => 'form-control selectpicker']) !!}
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection