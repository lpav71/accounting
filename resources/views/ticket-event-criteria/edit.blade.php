@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit ticket criteria') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('ticket-event-criteria.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($ticketEventCriterion, ['method' => 'PATCH','route' => ['ticket-event-criteria.update', $ticketEventCriterion->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('message_substring', __('Message substrings')) !!}
                {!! Form::text('message_substring', null, ['placeholder' => __('Message substrings'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('ticket_name_substring', __('Ticket name substrings')) !!}
                {!! Form::text('ticket_name_substring', null, ['placeholder' => __('Ticket name substrings'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('messages_count', __('Messages count')) !!}
                {!! Form::input('number','messages_count', null, ['placeholder' => __('Messages count'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('ticket_theme_id', __('Ticket theme')) !!}
                {!! Form::select('ticket_theme_id', $ticketThemes, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('creator_user_id', __('Ticket creator')) !!}
                {!! Form::select('creator_user_id', $users, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('performer_user_id', __('Ticket performer')) !!}
                {!! Form::select('performer_user_id', $users, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('ticket_priority_id', __('Ticket priority')) !!}
                {!! Form::select('ticket_priority_id', $ticketPriorities, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('last_writer', __('Last message written by')) !!}
                {!! Form::select('last_writer', $lastWriters, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('weekday_id[]', __('Weekdays')) !!}
                {!! Form::select('weekday_id[]', $weekdays, $ticketEventCriterion->weekdays()->pluck('weekday_id'), ['multiple','class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('time', __('Time from last message')) !!}
                {!! Form::input('number','last_message_time', null, ['multiple','class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection