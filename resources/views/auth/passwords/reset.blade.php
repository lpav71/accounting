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
                <p class="text-center">{{ __('ENTER E-MAIL AND NEW PASSWORD FOR RESET') }}</p>
                {{ Form::open(['method' => 'POST', 'route' => 'password.request']) }}
                {{ Form::input('hidden', 'token', $token) }}
                <div class="form-group">
                    {{ Form::label('email', __('E-Mail')) }}
                    {{ Form::input('email', 'email', old('email'), ['class' => 'form-control underlined'.($errors->has('email') ? ' is-invalid' : ''), 'required', 'autofocus', 'placeholder' => __('Enter email address')]) }}
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
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
