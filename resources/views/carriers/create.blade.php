@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Carrier') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('carriers.index') }}"> {{ __('Back') }}</a>
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



    {!! Form::open(['route' => 'carriers.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name').':') !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('url_link', __('URL carrier link').':') !!}
                {!! Form::text('url_link', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_internal', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_internal', __('Is internal carrier'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('city_id', __('City'), ['class' => 'form-label']) !!}
                {!! Form::select('city_id',\App\City::pluck('name','id')->prepend('undefined', 0), null , [ 'class'=>'form-control']) !!}
            </div>
            <div class="form-group">
                    {!! Form::label('carrier_type_id', __('Carrier type'), ['class' => 'form-label']) !!}
                    {!! Form::select('carrier_type_id',\App\CarrierType::pluck('name','id'), null , [ 'class'=>'form-control']) !!}    
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('close_order_task', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('close_order_task', __('Close order task'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('self_shipping', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('self_shipping', __('Self shipping'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('config', __('Config').':') !!}
                {!! Form::textarea('config', null, ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection