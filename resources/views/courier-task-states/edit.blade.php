@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Courier tasks edit') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('courier-task-states.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($courierTaskState, ['route' => ['courier-task-states.update', $courierTaskState->id],'method'=>'PATCH']) !!}
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
                {!! Form::select('previous_states_id[]', $courierTaskStates, $courierTaskState->previousStates->pluck('id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group form-check">
                {!! Form::checkbox('is_successful', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_successful', __('Is successful'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_failure', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_failure', __('Is failure'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_new', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_new', __('Is new'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_courier_state', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_courier_state', __('Courier task states'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
    </div>
    <div class="col-12 text-left">
        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
    </div>
    </div>
    {!! Form::close() !!}
@endsection