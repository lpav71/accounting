@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create new expense') }}</h2>
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
    {!! Form::open(['route' => 'expense-settings.store', 'method' => 'post']) !!}
    <div class="col-12 mt-3">
        {!! Form::label('name', __('Setting name')) !!}
        {!! Form::textarea('name', null, ['class' => 'form-control', 'rows' => 2, 'required' => true]) !!}
    </div>
    <div class="col-12 mt-3">
        {!! Form::label('summ', __('Extense summ')) !!}
        {!! Form::input('text', 'summ', null, ['class' => 'form-control', 'required' => true, 'step' => 0.01]) !!}
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            {!! Form::label('brand_id[]', __('Manufacturers')) !!}
            {!! Form::select('brand_id[]', $brands, null, ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            {!! Form::label('carrier_group_id[]', __('Carrier groups')) !!}
            {!! Form::select('carrier_group_id[]', \App\CarrierGroup::all()->pluck('name', 'id'), null, ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            {!! Form::label('carrier_id[]', __('Carriers')) !!}
            {!! Form::select('carrier_id[]', $carriers, null, ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            {!! Form::label('utm_campaign_id', __('Utm Campaigns')) !!}
            {!! Form::select('utm_campaign_id', $utms, 0, ['class' => 'form-control selectpicker-searchable']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            {!! Form::label('category_id', __('Categories')) !!}
            {!! Form::select('category_id', $categories, 0, ['class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            {!! Form::label('order_state_id[]', __('Order States')) !!}
            {!! Form::select('order_state_id[]', $orderStates, null, ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            {!! Form::label('channels[]', __('Channels')) !!}
            {!! Form::select('channels[]', $channels, null, ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            {!! Form::label('expense_category_id', __('Expense category')) !!}
            {!! Form::select('expense_category_id', $expenseCategories, 0, ['class' => 'form-control selectpicker']) !!}
        </div>
    </div>
    <div class="col-12 text-left mb-5 mt-5">
        {!! Form::button(__('Submit'), ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
    </div>
    {!! Form::close() !!}
@endsection