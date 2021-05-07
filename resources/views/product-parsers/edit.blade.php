@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Edit Product Parser') }}</h4>
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
    @php
        {{
        /**
          * @var $parser \App\Parser
          **/
        }}
    @endphp
    {!! Form::model($parser, ['method' => 'PATCH','route' => ['product-parsers.update', $parser->id]]) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-12">
                <div class="form-group">
                    {!! Form::label('name', __('Name')) !!}
                    {!! Form::text('name', $parser->name, ['class' => 'form-control']) !!}
                </div>
        </div>
        <div class="col-xs-12 col-sm-12">
            <div class="form-group">
                {!! Form::label('interval', __('Interval between querries (microsecond)')) !!}
                {!! Form::text('interval', $parser->interval, ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12">
            <div class="form-group">
                {!! Form::label('link', __('Link:')) !!}
                {!! Form::textarea('link', $parser->link, ['class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_active', 1, $parser->is_active, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_active', __('Active'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        @foreach($settings as $settingName => $setting)
            <div class="col-xs-12 col-sm-6">
                <div class="form-group">
                    {!! Form::label('settings['.$settingName.']', $settingName) !!}
                    {!! Form::text('settings['.$settingName.']', isset($parser->settings[$settingName]) ? $parser->settings[$settingName] : null, ['class' => 'form-control', 'required']) !!}
                </div>
            </div>
        @endforeach
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary btn-sm">{{ __('Save Parser') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection