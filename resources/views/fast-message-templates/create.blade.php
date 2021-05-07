@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create new message template') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('fast-message-templates.index') }}"> {{ __('Back') }}</a>
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



    {!! Form::open(['route' => 'fast-message-templates.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('message', __('Message')) !!}
                {!! Form::textarea('message', null, ['placeholder' => __('Message'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('type', __('Type:')) !!}
                {!! Form::text('type', null, ['placeholder' => __('Type'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('is_track_notification', __('Track number notification template').':') !!}
                {!! Form::checkbox('is_track_notification',1, null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('channels[]', __('Channels').':') !!}
                {!! Form::select('channels[]', \App\Channel::whereContentControl()->orderBy('name')->pluck('name','id'), null , ['title'=>__('Choose channels'), 'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
        <div>
            <h3>{{__('Template replacements')}}</h3>
            <table class="table">
                <tr>
                    <td>{Order.number}</td>
                    <td>{{__('Order number')}}</td>
                </tr>
                <tr>
                    <td>{Order.date}</td>
                    <td>{{__('Order date')}}</td>
                </tr>
                <tr>
                    <td>{Order.delivery_city}</td>
                    <td>{{__('Order delivery city')}}</td>
                </tr>
                <tr>
                    <td>{Order.delivery_address}</td>
                    <td>{{__('Order delivery address')}}</td>
                </tr>
                <tr>
                    <td>{Order.date_estimated_delivery}</td>
                    <td>{{__('Order estimate delivery date')}}</td>
                </tr>
                <tr>
                    <td>{Order.delivery_start_time}</td>
                    <td>{{__('Order delivery start time')}}</td>
                </tr>
                <tr>
                    <td>{Order.delivery_end_time}</td>
                    <td>{{__('Order delivery end time')}}</td>
                </tr>
                <tr>
                    <td>{Order.delivery_shipping_number}</td>
                    <td>{{__('Shipping number')}}</td>
                </tr>
                <tr>
                    <td>{Order.delivery_address_comment}</td>
                    <td>{{__('Order delivery comment')}}</td>
                </tr>
                <tr>
                    <td>{Customer.name}</td>
                    <td>{{__('Customer name')}}</td>
                </tr>
                <tr>
                    <td>{Customer.phone}</td>
                    <td>{{__('Customer phone')}}</td>
                </tr>
                <tr>
                    <td>{Channel.name}</td>
                    <td>{{__('Channel name')}}</td>
                </tr>
                <tr>
                    <td>{Channel.phone}</td>
                    <td>{{__('Channel phone')}}</td>
                </tr>

        </div>
    </div>
    {!! Form::close() !!}


@endsection