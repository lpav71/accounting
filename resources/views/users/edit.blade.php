@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit User') }}</h2>
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


    {!! Form::model($user, ['method' => 'PATCH','route' => ['users.update', $user->id]]) !!}
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
                {!! Form::select('roles[]', $roles, $userRole, ['class' => 'form-control','multiple']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('alternate_user_id', __('Alternate')) !!}
                {!! Form::select('alternate_user_id', $users, $user->alternate_user_id, ['class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('color', __('Color:')) !!}
                {!! Form::text('color', null, ['placeholder' => '#FFFFFF','class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('count_operation', __('Count operation per page')) !!}
                {!! Form::input('number', 'count_operation', $user->count_operation ? $user->count_operation : 0, ['placeholder' => __('Count operation per page'),'class' => 'form-control']) !!}
            </div>
        </div>
        @if(!empty($user->getRoleNames()))
            @if(in_array('courier', $userRole->toArray()))
                <div class="col-xs-12 col-sm-6">
                    <div class="form-group">
                        {!! Form::label('routeList', __('Route List')) !!}
                        {!! Form::select('routeList', $routeLists, $user->routeList()->pluck('id','id'), ['class' => 'form-control selectpicker-searchable']) !!}
                    </div>
                </div>
            @endif
                @if(in_array('manager', $userRole->toArray()))
                    <div class="col-xs-12 col-sm-6">
                        <div class="form-group">
                            {!! Form::label('channels[]', __('Channels')) !!}
                            {!! Form::select('channels[]', $channels, $user->channels, ['class' => 'form-control selectpicker-searchable', 'multiple']) !!}
                        </div>
                    </div>
                    @endif
        @endif
        <div class="col-12">
            <div class="form-group">
                {!! Form::checkbox('is_not_working', 1, null) !!}
                {!! Form::label('is_not_working', __('Is not working')) !!}
            </div>
            <div class="form-group">
                {!! Form::checkbox('is_crm', 1, null) !!}
                {!! Form::label('is_crm', __('CRM')) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection