@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{$ticket->name}}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('tickets.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($ticket, ['method' => 'PATCH','route' => ['tickets.update', $ticket->id]]) !!}
    <div class="row">
        <div class="col-sm-2">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                {!! Form::label('ticket_state_id', __('Ticket state')) !!}
                {!! Form::select('ticket_state_id', $ticketStates, $ticket->currentState()['id'], ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                {!! Form::label('ticket_theme_id', __('Ticket theme')) !!}
                {!! Form::select('ticket_theme_id', $ticketThemes, $ticket->ticket_theme_id, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                {!! Form::label('ticket_priority_id', __('Ticket priority')) !!}
                {!! Form::select('ticket_priority_id', $ticketPriorities, $ticket->ticket_priority_id, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-sm-1">
            <div class="col-12 text-left">
                <span>{{__('Creator')}}</span><br>
                <span>{{$ticket->creator->name}}</span><br>
            </div>
        </div>
        <div class="col-sm-2">
            <div class="col-12 text-left">
                <span>{{__('Ticket performer')}}</span><br>
                @if(!empty($ticket->performer))
                    <span>{{$ticket->performer->name}}</span><br>
                @endif
            </div>
        </div>
        <div class="col-sm-1">
            <div class="col-12 text-left">
                <span>{{__('Members')}}</span><br>
                @foreach($ticket->users as $user)
                <span>{{$user->name}}</span><br>
                @endforeach
            </div>
        </div>
        <div class="col-sm-1">
            <div class="col-12 text-left">
                <p></p>
                <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
            </div>
        </div>

        <div class="col-sm-2">
            <p></p>
            <p></p>
            @if(isset($ticket->order))
            <h3>{{__('Order')}} № {{$ticket->order->getOrderNumber()}}</h3>
            @endif
        </div>

    </div>
    {!! Form::close() !!}
    <div id="ticket" data-id="{{$ticket->id}}" data-user-id="{{Auth::id()}}">

    </div>
@endsection