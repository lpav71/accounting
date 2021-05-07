@extends('layouts.app')

@section('content')

    {!! Form::open(['route' => array('rule-order-permission.update', $ruleOrderPermission->id),'method'=>'PUT']) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name').':') !!}
                {!! Form::text('name', $ruleOrderPermission->name, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('user_id', __('Users:')) !!}
                @php //dd($users, $ruleOrderPermission->user()->pluck('user_id','name')) @endphp
                {!! Form::select('users[]', $users, $ruleOrderPermission->user()->pluck('user_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('role_id', __('Roles')) !!}
                {!! Form::select('roles[]', $roles, $ruleOrderPermission->role()->pluck('role_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('channel_id', __('Shops')) !!}
                {!! Form::select('channel_id[]', $channels, $ruleOrderPermission->channel()->pluck('channel_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('order_state_id', __('Order states')) !!}
                {!! Form::select('order_state_id[]', $orderStates,$ruleOrderPermission->orderState()->pluck('order_state_id'),['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('carier_id', __('Carriers')) !!}
                {!! Form::select('carier_id[]', $carriers, $ruleOrderPermission->carrier()->pluck('carrier_id'), ['multiple' => true, 'class' => 'form-control selectpicker carier']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('carrier_group_id', __('Carriers group')) !!}
                {!! Form::select('carrier_group_id[]', $carrier_groups, $ruleOrderPermission->carrierGroup()->pluck('carrier_group_id'), ['multiple' => true, 'class' => 'form-control selectpicker carrier_group_id']) !!}
            </div>
            <div class="col-12 text-left">
                <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
            </div>
        </div>
    </div>
    {!! Form::close() !!}

@endsection
