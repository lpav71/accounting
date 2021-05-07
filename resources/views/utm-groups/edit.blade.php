@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit Utm Group') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('utm-groups.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($utmGroup, ['route' => ['utm-groups.update', $utmGroup],'method'=>'PATCH']) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('rule', __('Rule:')) !!}
                {!! Form::text('rule', null, ['placeholder' => __('Rule'),'class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('sort_order', __('Sort order:')) !!}
                {!! Form::text('sort_order', null, ['class' => 'form-control', 'required']) !!}
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('indicator_clicks', __('Clicks:')) !!}
                        {!! Form::text('indicator_clicks', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('indicator_price_per_click_from', __('Min price per click:')) !!}
                        {!! Form::text('indicator_price_per_click_from', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('indicator_price_per_click_to', __('Max price per click:')) !!}
                        {!! Form::text('indicator_price_per_click_to', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('indicator_price_per_order_from', __('Min price per order:')) !!}
                        {!! Form::text('indicator_price_per_order_from', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('indicator_price_per_order_to', __('Max price per order:')) !!}
                        {!! Form::text('indicator_price_per_order_to', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('minimum_costs', __('Minimum costs')) !!}
                        {!! Form::text('minimum_costs', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('maximum_costs', __('Maximum costs')) !!}
                        {!! Form::text('maximum_costs', null, ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection