@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create new ticket event subscription') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('ticket-event-subscriptions.index') }}"> {{ __('Back') }}</a>
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



    {!! Form::open(['route' => 'ticket-event-subscriptions.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('event', __('Ticket event')) !!}
                {!! Form::select('event', $ticketEvents, null, ['class' => 'form-control selectpicker', 'required']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('ticket_event_criterion_id[]', __('Ticket event criteria')) !!}
                {!! Form::select('ticket_event_criterion_id[]', $ticketEventCriteria, null, ['multiple','class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('ticket_event_action_id[]', __('Ticket event actions')) !!}
                {!! Form::select('ticket_event_action_id[]', $ticketEventActions, null, ['multiple','class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection