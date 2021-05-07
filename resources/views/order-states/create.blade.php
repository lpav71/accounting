@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Order State') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('order-states.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::open(['route' => 'order-states.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('previous_order_states_id[]', __('Previous States:')) !!}
                {!! Form::select('previous_order_states_id[]', $orderStates, [null], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_sending_external_data', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_sending_external_data', __('Send data to the delivery service'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_confirmed',1, null, ['placeholder' => __('Confirmed'), 'class' => 'form-check-input']) !!}
                {!! Form::label('is_confirmed', __('Confirmed')) !!}
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
                {!! Form::label('need_order_detail_state_id[]', __('Need Order Detail states:')) !!}
                {!! Form::select('need_order_detail_state_id[]', $orderDetailStates, [null], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6" data-toggle="is_sending_external_data">
            <div class="form-group">
                {!! Form::label('need_one_order_detail_state_id[]', __('Need One Order Detail states:')) !!}
                {!! Form::select('need_one_order_detail_state_id[]', $orderDetailStates, [null], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6" data-toggle="is_sending_external_data">
            <div class="form-group">
                {!! Form::label('check_payment', __('Check payment:')) !!}
                {!! Form::select('check_payment', [0 => __('No'), 1 => __('Yes')], 0, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6" data-toggle="is_sending_external_data">
            <div class="form-group">
                {!! Form::label('check_carrier', __('Check carrier information')) !!}
                {!! Form::select('check_carrier', [0 => __('No'), 1 => __('Yes')], 0, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('color', __('Color:')) !!}
                {!! Form::text('color', null, ['placeholder' => __('#FFFFFF'),'class' => 'form-control']) !!}
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
                {!! Form::checkbox('is_new', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_new', __('Is new order'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_sent', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_sent', __('Is sent'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('next_auto_closing_status', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('next_auto_closing_status', __('Next auto closing status'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('cdek_not_load', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('cdek_not_load', __('Cdek states not load'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('inactive_order', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('inactive_order', __('Inactive order'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('inactive_order', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('inactive_order', __('Inactive order'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('check_certificates_number', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('check_certificates_number', __('Check certificates number'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('roles', __('Roles')) !!}
                {!! Form::select('roles[]', $roles, $roles, ['class' => 'form-control selectpicker', 'multiple' => 'true']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection
