@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Telephony account groups edit') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('telephony-account-groups.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($telephonyAccountGroup, ['method' => 'PATCH','route' => ['telephony-account-groups.update', $telephonyAccountGroup->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name').':') !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('telephony_account_id[]', __('Carriers')) !!}
                {!! Form::select('telephony_account_id[]', $telephonyAccounts, $telephonyAccountGroup->telephonyAccounts->pluck('id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('user_id[]', __('Users:')) !!}
                {!! Form::select('user_id[]', $users, $telephonyAccountGroup->users->pluck('id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="col-12 text-left">
                <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
@endsection