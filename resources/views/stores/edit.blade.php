@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit Store') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('stores.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($store, ['method' => 'PATCH','route' => ['stores.update', $store->id]]) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, array('placeholder' => __('Name'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('user_id[]', __('Users:')) !!}
                {!! Form::select('user_id[]', $users, $store->users()->pluck('user_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('user_id_with_operation_rights[]', __('Users with operation rights:')) !!}
                {!! Form::select('user_id_with_operation_rights[]', $users, $store->usersWithOperationRights()->pluck('user_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('user_id_with_reservation_rights[]', __('Users with reservation rights:')) !!}
                {!! Form::select('user_id_with_reservation_rights[]', $users, $store->usersWithReservationRights()->pluck('user_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('user_id_with_transfer_rights[]', __('Users with transfer rights:')) !!}
                {!! Form::select('user_id_with_transfer_rights[]', $users, $store->usersWithTransferRights()->pluck('user_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('limit', __('Daily limit of products on store')) !!}
                {!! Form::number('limit', null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection