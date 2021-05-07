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
                <p class="text-center">{{ __('SIGNUP TO GET INSTANT ACCESS') }}</p>
                {{ Form::open(['method' => 'POST', 'route' => 'register']) }}
                <div class="form-group">
                    {{ Form::label('name', __('Name')) }}
                    {{ Form::input('text', 'name', old('name'), ['class' => 'form-control underlined'.($errors->has('name') ? ' is-invalid' : ''), 'required', 'autofocus', 'placeholder' => __('Enter Name')]) }}
                    @if ($errors->has('name'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('name') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    {{ Form::label('email', __('E-Mail')) }}
                    {{ Form::input('email', 'email', old('email'), ['class' => 'form-control underlined'.($errors->has('email') ? ' is-invalid' : ''), 'required', 'placeholder' => __('Enter email address')]) }}
                    @if ($errors->has('email'))
                        <span class="invalid-feedback">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    {{ Form::label('password', __('Password')) }}
                    <div class="row">
                        <div class="col-sm-6">
                            {{ Form::input('password', 'password', null, ['class' => 'form-control underlined'.($errors->has('password') ? ' is-invalid' : ''), 'required', 'placeholder' => __('Enter password')]) }}
                            @if ($errors->has('password'))
                                <span class="invalid-feedback">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            {{ Form::input('password', 'password_confirmation', null, ['class' => 'form-control underlined'.($errors->has('password') ? ' is-invalid' : ''), 'required', 'placeholder' => __('Re-type password')]) }}
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    {{ Form::button(__('Sign Up'), ['type' => 'submit', 'class' => 'btn btn-block btn-primary']) }}
                </div>
                <div class="form-group">
                    <p class="text-muted text-center">{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Login!') }}</a>
                    </p>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
