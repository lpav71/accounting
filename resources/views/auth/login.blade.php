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
                <p class="text-center">{{ __('LOGIN TO CONTINUE') }}</p>
                {{ Form::open(['method' => 'POST','route' => 'login']) }}
                <div class="form-group">
                    {{ Form::label('email', __('E-Mail Address')) }}
                    {{ Form::input('email', 'email', old('email'), ['class' => 'form-control underlined'.($errors->has('email') ? ' is-invalid' : ''), 'required', 'autofocus', 'placeholder' => __('Enter email address')]) }}
                    @if ($errors->has('email'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    {{ Form::label('password', __('Password')) }}
                    {{ Form::input('password', 'password', null, ['class' => 'form-control underlined'.($errors->has('password') ? ' is-invalid' : ''), 'required', 'placeholder' => __('Enter password')]) }}
                    @if ($errors->has('password'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    <label for="remember">
                        {{ Form::input('checkbox', 'remember', old('remember'), ['class' => 'checkbox']) }}
                        <span>{{ __('Remember me') }}</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="forgot-btn pull-right">{{ __('Forgot password?') }}</a>
                </div>
                <div class="form-group">
                    {{ Form::button(__('Login'), ['type' => 'submit', 'class' => 'btn btn-block btn-primary']) }}
                </div>
                <div class="form-group">
                    <p class="text-muted text-center">
                        {{ __('Do not have an account?') }} <a href="{{ route('register') }}">{{ __('Sign Up!') }}</a>
                    </p>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
