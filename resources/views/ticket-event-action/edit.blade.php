@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit ticket actions') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('ticket-event-actions.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($ticketEventAction, ['method' => 'PATCH','route' => ['ticket-event-actions.update', $ticketEventAction->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('message_replace', __('Replace string, write as "string=>replace"')) !!}
                {!! Form::text('message_replace', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('add_user_id', __('Add user')) !!}
                {!! Form::select('add_user_id', $users->prepend('',''), null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('auto_message', __('Auto message')) !!}
                {!! Form::text('auto_message', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('ticket_priority_id', __('Change ticket priority')) !!}
                {!! Form::select('ticket_priority_id', $ticketPriorities, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('performer_user_id', __('Change ticket performer')) !!}
                {!! Form::select('performer_user_id', $users, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('notify', __('Send notification')) !!}
                {!! Form::select('notify', $notifiers, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
        <input id="ticket-users-input" name="users_to_add" type="hidden" value="">
    </div>
    {!! Form::close() !!}
    <div id="ticket-event-action-id" data-users="{{$usersNotAttachedJson}}" data-users-attached="{{$usersAttachedJson}}"></div>
    <div id="ticket-event-actions"></div>
@endsection