@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit Exchange State') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('product-exchange-states.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($productExchangeState, ['route' => ['product-exchange-states.update', $productExchangeState->id],'method'=>'PATCH']) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('previous_states_id[]', __('Previous States:')) !!}
                {!! Form::select('previous_states_id[]', $exchangeStates, $productExchangeState->previousStates->pluck('id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6" data-toggle="is_sending_external_data">
            <div class="form-group">
                {!! Form::label('new_order_detail_state_id', __('New Order Detail state on Change:')) !!}
                {!! Form::select('new_order_detail_state_id', $orderDetailStates->prepend(__('No'), 0), null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6" data-toggle="is_sending_external_data">
            <div class="form-group">
                {!! Form::label('new_exchange_order_detail_state_id', __('New Exchange Order Detail state on Change:')) !!}
                {!! Form::select('new_exchange_order_detail_state_id', $orderDetailStates->prepend(__('No'), 0), null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6" data-toggle="is_sending_external_data">
            <div class="form-group">
                {!! Form::label('need_order_detail_state_id[]', __('Need Order Detail states:')) !!}
                {!! Form::select('need_order_detail_state_id[]', $orderDetailStates, $productExchangeState->needOrderDetailStates->pluck('id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6" data-toggle="is_sending_external_data">
            <div class="form-group">
                {!! Form::label('need_one_order_detail_state_id[]', __('Need One Order Detail states:')) !!}
                {!! Form::select('need_one_order_detail_state_id[]', $orderDetailStates, $productExchangeState->needOneOrderDetailStates->pluck('id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6" data-toggle="is_sending_external_data">
            <div class="form-group">
                {!! Form::label('need_exchange_order_detail_state_id[]', __('Need Exchange Order Detail states:')) !!}
                {!! Form::select('need_exchange_order_detail_state_id[]', $orderDetailStates, $productExchangeState->needExchangeOrderDetailStates->pluck('id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6" data-toggle="is_sending_external_data">
            <div class="form-group">
                {!! Form::label('need_one_exchange_order_detail_state_id[]', __('Need One Exchange Order Detail states:')) !!}
                {!! Form::select('need_one_exchange_order_detail_state_id[]', $orderDetailStates, $productExchangeState->needOneExchangeOrderDetailStates->pluck('id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('check_payment', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('check_payment', __('Check payment'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_blocked_edit_order_details', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_blocked_edit_order_details', __('Blocked edit the order details'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_successful', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_successful', __('Is successful'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_failure', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_failure', __('Is failure'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_sent', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_sent', __('Is sent'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('next_auto_closing_status', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('next_auto_closing_status', __('Next auto closing status'), ['class' => 'form-check-label']) !!}
            </div>
            <div>
                <div class="form-group form-check">
                    {!! Form::checkbox('inactive_exchange', 1, null, ['class' => 'form-check-input']) !!}
                    {!! Form::label('inactive_exchange', __('Inactive exchange'), ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div>
                <div class="form-group form-check">
                    {!! Form::checkbox('shipment_available', 1, null, ['class' => 'form-check-input']) !!}
                    {!! Form::label('shipment_available', __('Shipment available'), ['class' => 'form-check-label']) !!}
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('color', __('Color:')) !!}
                {!! Form::text('color', null, ['placeholder' => '#FFFFFF','class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection