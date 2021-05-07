@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Channel') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('channels.index') }}"> {{ __('Back') }}</a>
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



    {!! Form::open(array('route' => 'channels.store','method'=>'POST', 'files' => true)) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, array('placeholder' => __('Name'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('url', __('Url:')) !!}
                {!! Form::text('url', null, array('placeholder' => __('Url'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('db_name', __('Database name')) !!}
                {!! Form::text('db_name', null, array('placeholder' => __('Database name'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('call_target_id', __('CallTargetId').':') !!}
                {!! Form::text('call_target_id', null, array('placeholder' => __('CallTargetId'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('go_proxy_url', __('Go proxy URL').':') !!}
                {!! Form::text('go_proxy_url', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('yandex_token', __('Yandex Token').':') !!}
                {!! Form::text('yandex_token', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('yandex_counter', __('Yandex Counter').':') !!}
                {!! Form::text('yandex_counter', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('google_counter', __('Google Counter').':') !!}
                {!! Form::text('google_counter', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('google_file', __('Google Key').':') !!}
                {!! Form::file('google_file', ['id' => 'google_file', 'class' => 'form-control-file', 'accept' => '.json']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('phone', __('Phone').':') !!}
                {!! Form::text('phone', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('check_certificate_token', __('Check certificate token').':') !!}
                {!! Form::text('check_certificate_token', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('template_name', __('Name for template').':') !!}
                {!! Form::text('template_name', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('upload_address', __('Products upload address').':') !!}
                {!! Form::text('upload_address', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('download_address', __('Product download address').':') !!}
                {!! Form::text('download_address', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('upload_key', __('Products upload key').':') !!}
                {!! Form::text('upload_key', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection
