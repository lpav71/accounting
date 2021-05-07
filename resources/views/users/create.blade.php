@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New User') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('users.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::open(array('route' => 'users.store','method'=>'POST')) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('email', __('E-mail:')) !!}
                {!! Form::text('email', null, ['placeholder' => __('E-mail'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('phone', __('Phone')) !!}
                {!! Form::text('phone', null, ['placeholder' => __('Phone'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                {!! Form::label('password', __('Password:')) !!}
                {!! Form::password('password', ['placeholder' => __('Password'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                {!! Form::label('confirm-password', __('Confirm password:')) !!}
                {!! Form::password('confirm-password', ['placeholder' => __('Confirm password'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('roles[]', __('Roles:')) !!}
                {!! Form::select('roles[]', $roles, [], ['class' => 'form-control','multiple']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('color', __('Color:')) !!}
                {!! Form::text('color', null, ['placeholder' => '#FFFFFF','class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection