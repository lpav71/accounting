@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit telegram report settings') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('telegram-report-settings.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($telegramReportSetting, ['method' => 'PATCH','route' => ['telegram-report-settings.update', $telegramReportSetting->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('chat_id', __('Chat id')) !!}
                {!! Form::text('chat_id', null, ['placeholder' => __('Chat id'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('time', __('Time of sending report')) !!}
                {!! Form::time('time', null, ['placeholder' => __('Time'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('confirm_time', __('Confirmation time')) !!}
                {!! Form::time('confirm_time', null, ['placeholder' => __('Confirmation time'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('task_states[]', __('Task States').':') !!}
                {!! Form::select('task_states[]', $taskStates, $currentTaskStates, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('order_states[]', __('Order States').':') !!}
                {!! Form::select('order_states[]', $orderStates, $currentOrderStates, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('order_detail_states[]', __('Order Detail States').':') !!}
                {!! Form::select('order_detail_states[]', $orderDetailStates, $currentOrderDetailStates, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('users[]', __('Users').':') !!}
                {!! Form::select('users[]', $users, null, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection