@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit Order Detail State') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('order-detail-states.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($orderDetailState, ['method' => 'PATCH','route' => ['order-detail-states.update', $orderDetailState->id]]) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('full_name', __('Full Name:')) !!}
                {!! Form::text('full_name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('previous_order_detail_states_id[]', __('Previous States:')) !!}
                {!! Form::select('previous_order_detail_states_id[]', $orderDetailStates, $orderDetailState->previousStates()->pluck('order_detail_state_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('store_operation', __('Store operation:')) !!}
                {!! Form::select('store_operation', $storeOperations, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('currency_operation_by_order', __('Virtual operation by customer debt:')) !!}
                {!! Form::select('currency_operation_by_order', $currencyOperations, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('product_operation_by_order', __('Virtual operation by products:')) !!}
                {!! Form::select('product_operation_by_order', $productOperations, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('owner_type', __('Where can be used status:')) !!}
                {!! Form::select('owner_type', $stateOwners, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('new_order_detail_owner_type', __('New order Detail owner:')) !!}
                {!! Form::select('new_order_detail_owner_type', $orderDetailOwners, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('is_hidden', __('Hidden state:')) !!}
                {!! Form::select('is_hidden', [0 => __('No'), 1 => __('Yes')], null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('need_payment', __('Payable:')) !!}
                {!! Form::select('need_payment', [0 => __('No'), 1 => __('Yes')], null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_block_editing_order_detail', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_block_editing_order_detail', __('Blocked edit the order details'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_block_deleting_order_detail', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_block_deleting_order_detail', __('Blocked delete the order details'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_block_editing_store', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_block_editing_store', __('Blocked edit store for the order details'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_courier_state', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_courier_state', __('Is Courier State'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_delivered', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_delivered', __('Is delivered'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_returned', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_returned', __('Is returned'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_new', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_new', __('New'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_sent', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_sent', __('Is sent'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_reserved', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_reserved', __('Is_reserved'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_shipped', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_shipped', __('Shipped'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('crediting_certificate', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('crediting_certificate', __('Crediting certificate'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('writing_off_certificate', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('writing_off_certificate', __('Writing off certificate'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('zeroing_certificate_number', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('zeroing_certificate_number', __('Zeroing certificate number'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection