@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit Task State') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('task-states.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($taskState, ['method' => 'PATCH','route' => ['task-states.update', $taskState->id]]) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('previous_task_states_id[]', __('Previous States:')) !!}
                {!! Form::select('previous_task_states_id[]', $taskStates, $taskState->previousStates()->pluck('task_state_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('color', __('Color:')) !!}
                {!! Form::text('color', null, ['placeholder' => __('#FFFFFF'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('is_closed', __('General task state:')) !!}
                {!! Form::select('is_closed', [0 => __('Opened'), 1 => __('Closed')], null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-6 col-sm-2">
            <div class="form-group">
                {!! Form::label('is_new', __('Is new state:')) !!}
                {!! Form::checkbox('is_new', 1, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection