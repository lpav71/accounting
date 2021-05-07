@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Cashbox') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('cashboxes.index') }}"> {{ __('Back') }}</a>
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



    {!! Form::open(array('route' => 'cashboxes.store','method'=>'POST')) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, array('placeholder' => __('Name'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group" id="part-products">
                {!! Form::label('user_id[]', __('Users:')) !!}
                {!! Form::select('user_id[]', $users, [], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_non_cash', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_non_cash', __('Is non cash cashbox'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('for_certificates', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('for_certificates', __('For certificates'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('user_id_with_transfer_rights[]', __('Users with transfer rights:')) !!}
                {!! Form::select('user_id_with_transfer_rights[]', $users, [], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('user_id_with_confirm_rights[]', __('Users with confirm rights:')) !!}
                {!! Form::select('user_id_with_confirm_rights[]', $users, [], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('limit', __('Cashbox limit')) !!}
                {!! Form::text('limit', null, array('placeholder' => __('Cashbox limit'),'class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('operation_limit', __('Operation limit')) !!}
                {!! Form::text('operation_limit', null, array('placeholder' => __('Operation limit'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection