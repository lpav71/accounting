@extends('layouts.app')

@section('content')
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
    <a class="btn btn-sm btn-primary" href="{{ route('rule-order-permission.index') }}"> Назад</a>
    <p></p>
    {!! Form::open(['route' => 'rule-order-permission.store','method'=>'POST','class'=>'my_form']) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name').':') !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('user_id', __('Users:')) !!}
                {!! Form::select('user_id[]', $users,'', ['multiple' => true, 'class' => 'form-control selectpicker user']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('role_id[]', __('Roles')) !!}
                {!! Form::select('role_id[]', $roles,'', ['multiple' => true, 'class' => 'form-control selectpicker role']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('channel_id[]', __('Shops')) !!}
                {!! Form::select('channel_id[]', $channels,'', ['multiple' => true, 'class' => 'form-control selectpicker channel']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('order_state_id[]', __('Order states')) !!}
                {!! Form::select('order_state_id[]', $order_states,'',['multiple' => true, 'class' => 'form-control selectpicker order_state']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('carier_id[]', __('Carriers')) !!}
                {!! Form::select('carier_id[]', $carriers,'', ['multiple' => true, 'class' => 'form-control selectpicker carier']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('carrier_group_id[]', __('Carriers group')) !!}
                {!! Form::select('carrier_group_id[]', $carrier_groups,'', ['multiple' => true, 'class' => 'form-control selectpicker carrier_group_id']) !!}
            </div>
            <div class="col-12 text-left">
                <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
            </div>
        </div>
    </div>
    {!! Form::close() !!}



@endsection
