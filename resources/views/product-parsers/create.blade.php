@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Create New Product Parser') }}</h4>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('product-parsers.index') }}"> {{ __('Back') }}</a>
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
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning">
            <p>{{ $message }}</p>
        </div>
    @endif

    {!! Form::open(['route' => 'product-parsers.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-12">
            <div class="form-group">
                {!! Form::label('name', __('Name')) !!}
                {!! Form::text('name', null, ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12">
            <div class="form-group">
                {!! Form::label('interval', __('Interval between querries (microsecond)')) !!}
                {!! Form::text('interval', null, ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12">
            <div class="form-group">
                {!! Form::label('link', __('Link:')) !!}
                {!! Form::textarea('link', null, ['class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_active', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_active', __('Active'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        @foreach($settings as $settingName => $setting)
            <div class="col-xs-12 col-sm-6">
                <div class="form-group">
                    {!! Form::label('settings['.$settingName.']', $settingName) !!}
                    {!! Form::text('settings['.$settingName.']', null, ['class' => 'form-control', 'required']) !!}
                </div>
            </div>
        @endforeach
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary btn-sm">{{ __('Save Parser') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection