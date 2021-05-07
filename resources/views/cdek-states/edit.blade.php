@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit category') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('categories.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($cdekState, ['method' => 'PATCH','route' => ['cdek-states.update', $cdekState->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('state_code', __('State code')) !!}
                {!! Form::text('state_code', null, ['placeholder' => __('State code'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <table>
                <thead>
                <tr>
                    <th>{{__('Task existing')}}</th>
                    <th>{{__('Daily')}}</th>
                    <th>{{__('Last state')}}</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>{!! Form::checkbox( 'need_task', 1, null , ['class' => 'form-control']) !!}</td>
                    <td>{!! Form::checkbox( 'is_daily', 1, null , ['class' => 'form-control']) !!}</td>
                    <td>{!! Form::checkbox( 'is_last_state', 1, null , ['class' => 'form-control']) !!}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('task_name', __('Task theme')) !!}
                {!! Form::text('task_name', null, ['placeholder' => __('Task theme'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('task_description', __('Task description')) !!}
                {!! Form::text('task_description', null, ['placeholder' => __('Task theme'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('task_priority_id', __('Task priority')) !!}
                {!! Form::select('task_priority_id', $task_priorities, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('task_type_id', __('Task type')) !!}
                {!! Form::select('task_type_id', $taskTypes, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('task_date_diff', __('Task deadline date days difference')) !!}
                {!! Form::text('task_date_diff', null, ['placeholder' => __('Task deadline date days difference'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
        
    </div>
    {!! Form::close() !!}
@endsection
