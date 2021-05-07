@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Create New Task') }}</h4>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('tasks.index') }}"> {{ __('Back') }}</a>
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
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning">
            <p>{{ $message }}</p>
        </div>
    @endif



    {!! Form::open(['route' => 'tasks.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Theme:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Theme'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('task_state_id', __('Task state:')) !!}
                {!! Form::select('task_state_id', $taskStates, null, ['class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('description', __('Description:')) !!}
                {!! Form::textarea('description', null, ['placeholder' => __('Description'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('task_priority_id', __('Priority:')) !!}
                {!! Form::select('task_priority_id', $priorities, null, ['class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('task_type_id', __('Type:')) !!}
                {!! Form::select('task_type_id', $types, null, ['class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('order_id', __('Order:')) !!}
                {!! Form::select('order_id', $orders, isset($order) ? $order->id : null, ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('customer_id', __('Customer:')) !!}
                {!! Form::select('customer_id', $customers, isset($customer) ? $customer->id : null, ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('performer_user_id', __('Performer:')) !!}
                {!! Form::select('performer_user_id', $users, null, ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('deadline_date', __('Deadline date:')) !!}
                        {!! Form::text('deadline_date', null, ['class' => 'form-control date-picker', 'required']) !!}
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('deadline_time', __('Deadline time:')) !!}
                        {!! Form::text('deadline_time', null, ['class' => 'form-control time-picker', 'required']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary btn-sm">{{ __('Save Task') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection
