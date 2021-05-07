@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Route Point State') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('route-point-states.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::open(['route' => 'route-point-states.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('previous_states_id[]', __('Previous States:')) !!}
                {!! Form::select('previous_states_id[]', $routePointStates, [], ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('new_order_state_id', __('New Order state on Change:')) !!}
                {!! Form::select('new_order_state_id', $orderStates->prepend(__('No'), 0), null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('new_product_return_state_id', __('New Product Return state on Change:')) !!}
                {!! Form::select('new_product_return_state_id', $productReturnStates->prepend(__('No'), 0), null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('new_product_exchange_state_id', __('New Product Exchange state on Change:')) !!}
                {!! Form::select('new_product_exchange_state_id', $productExchangeStates->prepend(__('No'), 0), null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group form-check">
                {!! Form::checkbox('is_detach_point_object', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_detach_point_object', __('Detach point object on Change'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_attach_detached_point_object', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_attach_detached_point_object', __('Attach detached point object on Change'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_need_comment_to_point_object', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_need_comment_to_point_object', __('Need comment to point object on Change'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_successful', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_successful', __('Is successful'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_failure', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_failure', __('Is failure'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group form-check">
                {!! Form::checkbox('is_new', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_new', __('Is new'), ['class' => 'form-check-label']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('color', __('Color:')) !!}
                {!! Form::text('color', null, ['placeholder' => '#FFFFFF','class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection