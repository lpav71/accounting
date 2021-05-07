@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create new ticket state') }}</h2>
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



    {!! Form::open(['route' => 'tickets.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('ticket_theme_id', __('Ticket theme')) !!}
                {!! Form::select('ticket_theme_id', $themes->prepend(__('Choose theme'),''), null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('ticket_priority_id', __('Ticket priority')) !!}
                {!! Form::select('ticket_priority_id', $priorities->prepend(__('Choose priority'),''), $defaultPriorityId, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('order_id', __('Order:')) !!}
                {!! Form::select('order_id', $orders->prepend(__('Order'),''), null, ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection