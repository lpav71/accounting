@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit Task Priority') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('task-priorities.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($taskPriority, ['method' => 'PATCH','route' => ['task-priorities.update', $taskPriority->id]]) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('rate', __('Rate:')) !!}
                {!! Form::text('rate', null, ['placeholder' => __('Rate'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('is_urgent', __('Urgent').':') !!}
                {!! Form::checkbox('is_urgent',1, null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('is_very_urgent', __('Very urgent').':') !!}
                {!! Form::checkbox('is_very_urgent',1, null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('is_normal', __('Normal priority').':') !!}
                {!! Form::checkbox('is_normal',1, null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection