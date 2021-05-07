@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Cashbox Search') }}</h2>
            </div>
        </div>
    </div>
    {!! Form::open(['route' => 'cashbox.show.operation', 'method'=>'GET']) !!}
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            {!! Form::label('cashbox_id[]', __('Cashboxes')) !!}
            {!! Form::select('cashbox_id[]', $cashboxes, $cashboxes, ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
        </div>
        <div>
            <div class="form-group">
                {!! Form::label('users_id[]', __('Users')) !!}
                {!! Form::select('users_id[]', $users, $users, ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div>
            <div class="form-group">
                {!! Form::label('operation_type', __('Operation type')) !!}
                {!! Form::select('operation_type', ['A' => __('All'), 'C' => __('Credit'), 'D' => __('Debit')], [], ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div>
            <div class="form-group">
                {!! Form::label('roles[]', __('Roles')) !!}
                {!! Form::select('roles[]', $roles, $roles, ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div>
            <div class="form-group">
                {!! Form::label('sum', __('Sum')) !!}
                {!! Form::number('sum', null, ['class' => 'form-control']) !!}
            </div>
        </div>
            @php
                use Illuminate\Support\Carbon;
            @endphp
        <div class="d-flex clearfix" id="deliveryDate">
            <div>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        {!! Form::label('from', __('From'), ['class' => 'form-control form-control-sm m-0']) !!}
                    </div>
                    <div class="input-group-append">
                        {!! Form::text('from', Carbon::yesterday()->format('d-m-Y'), ['class' => 'form-control form-control-sm date', 'autocomplete' => 'off']) !!}
                    </div>
                </div>
            </div>
            <div>
                <div class="input-group input-group-sm ml-1">
                    <div class="input-group-prepend">
                        {!! Form::label('to', __('To'), ['class' => 'form-control form-control-sm m-0']) !!}
                    </div>
                    <div class="input-group-append">
                        {!! Form::text('to', Carbon::today()->format('d-m-Y'), ['class' => 'form-control form-control-sm date', 'autocomplete' => 'off']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 mt-3">
            {!! Form::label('phrase', __('Search phrase')) !!}
            {!! Form::textarea('phrase', null, ['class' => 'form-control', 'rows' => 2]) !!}
        </div>
    </div>
    <div class="col-12 text-left mb-5 mt-5">
        {!! Form::button(__('Submit'), ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
    </div>
    {!! Form::close() !!}
@endsection
