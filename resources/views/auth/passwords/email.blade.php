@extends('layouts.app')

@section('content')
    <div class="auth-container">
        <div class="card">
            <header class="auth-header">
                <h1 class="auth-title">
                    @include('_common.logo.logo')
                    {{ __(config('app.name', 'Volga Acco')) }}
                </h1>
            </header>
            <div class="auth-content">
                <p class="text-center">{{ __('PASSWORD RECOVER') }}</p>
                <p class="text-muted text-center">
                    <small>{{ __('Enter your email address to recover your password.') }}</small>
                </p>
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                {{ Form::open(['method' => 'POST', 'route' => 'password.email']) }}
                <div class="form-group">
                    {{ Form::label('email', __('E-Mail')) }}
                    {{ Form::input('email', 'email', old('email'), ['class' => 'form-control underlined'.($errors->has('email') ? ' is-invalid' : ''), 'required', 'autofocus', 'placeholder' => __('Your email address')]) }}
                    @if ($errors->has('email'))
                        <span class="invalid-feedback">
                                <strong>{{ $errors->first('email') }}</strong>
                            </span>
                    @endif
                </div>
                <div class="form-group">
                    {{ Form::button(__('Reset'), ['type' => 'submit', 'class' => 'btn btn-block btn-primary']) }}
                </div>
                <div class="form-group clearfix">
                    <a class="pull-left" href="{{ route('login') }}">{{ __('return to Login') }}</a>
                    <a class="pull-right" href="{{ route('register') }}">{{ __('Sign Up!') }}</a>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
