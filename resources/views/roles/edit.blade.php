@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit Role') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('roles.index') }}"> {{ __('Back') }}</a>
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


    {!! Form::model($role, ['method' => 'PATCH','route' => ['roles.update', $role->id]]) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>{{ __('Name:') }}</strong>
                {!! Form::text('name', null, array('placeholder' => __('Name'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>{{ __('Courier:') }}</strong>
                {!! Form::checkbox('is_courier', 1, null) !!}
                <strong>{{ __('Employee') }}</strong>
                {!! Form::checkbox('is_manager', 1, null) !!}
                <strong>{{ __('CRM') }}</strong>
                {!! Form::checkbox('is_crm', 1, null) !!}
                <strong>{{ __('Task performer') }}</strong>
                {!! Form::checkbox('is_task_performer', 1, null) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>{{ __('Permissions:') }}</strong>
                <br/>
                @foreach($permission->groupBy(function ($item) {return explode('-', $item->name)[0];})->sortBy(function ($item, $key) {return $key;}) as $permissionGroup)
                    <div class="row box-placeholder">
                    @foreach($permissionGroup as $value)
                        <label class="col-xs-12 col-sm-3">{{ Form::checkbox('permission[]', $value->id, in_array($value->id, $rolePermissions) ? true : false, array('class' => 'name')) }}
                            {{ $value->name }}</label>
                    @endforeach
                    </div>
                @endforeach
            </>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12 text-center">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection
