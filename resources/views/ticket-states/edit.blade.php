@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit ticket state') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('ticket-states.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($ticketState, ['method' => 'PATCH','route' => ['ticket-states.update', $ticketState->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('previous_ticket_states_id[]', __('Previous States:')) !!}
                {!! Form::select('previous_ticket_states_id[]', $ticketStates, $ticketState->previousStates()->pluck('ticket_state_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_default', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_default', __('Is default'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_closed', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_closed', __('Is closed'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection