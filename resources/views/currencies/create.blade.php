@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Currency') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('currencies.index') }}"> {{ __('Back') }}</a>
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



    {!! Form::open(['route' => 'currencies.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('currency_rate', __('Currency rate').':') !!}
                {!! Form::text('currency_rate', null, ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
                <div class="form-group">
                    {!! Form::label('iso_code', __('Currency ISO code').':') !!}
                    {!! Form::text('iso_code', null, ['class' => 'form-control']) !!}
                </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('is_default', __('Default').':') !!}
                {!! Form::checkbox('is_default',1, null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection