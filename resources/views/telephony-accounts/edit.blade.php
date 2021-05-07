@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Carrier group edit') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('carrier-group.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($telephonyAccount, ['method' => 'PATCH','route' => ['telephony-accounts.update', $telephonyAccount->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="col-12">
                <div class="form-group">
                    {!! Form::label('name', __('Name').':') !!}
                    {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    {!! Form::label('login', __('Login:').' manader1@74952352067.tel.matrixmobile.ru, 9096723055@mpbx.sip.beeline.ru') !!}
                    {!! Form::text('login', null, ['placeholder' => __('Login:'),'class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    {!! Form::label('telephony_name', __('Telephony name').' matrixmobile, beeline') !!}
                    {!! Form::text('telephony_name', null, ['placeholder' => __('Telephony name'),'class' => 'form-control']) !!}
                </div>
            </div>
            <div class="col-12 text-left">
                <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
@endsection