@extends('layouts.app')


@section('content')
    {!! Form::open(array('route' => 'orders.store','method'=>'POST')) !!}
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4 class="float-left mr-3">{{ __('Create New Order') }}</h4>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('orders.index') }}"> {{ __('Back') }}</a>
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




    <div class="row" id="create-order">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('channel_id', __('Channel:')) !!}
                {!! Form::select('channel_id', $channels, [], ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('first_name', __('Name')) !!}
                {!! Form::text('first_name', null, array('placeholder' => __('Name'),'class' => 'form-control', 'required')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('phone', __('Phone')) !!}
                {!! Form::text('phone', null, array('placeholder' => __('Phone'),'class' => 'form-control', 'required')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('email', __('Email')) !!}
                {!! Form::text('email', null, array('placeholder' => __('Email'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('delivery_address_comment', __('Address')) !!}
                {!! Form::text('delivery_address_comment', null, array('placeholder' => __('Address'),'class' => 'form-control', 'required')) !!}
            </div>
        </div>
        <table class="table table-bordered table-sm m-0 not-hidden">
            <thead class="thead-light">
            <tr class="small text-center">
                <th class="align-middle">{{ __('Product') }}</th>
                <th class="align-middle certificate-number">{{ __('Certificate number') }}</th>
                <th class="align-middle">{{ __('Reference') }}</th>
                <th class="align-middle">{{ __('Price') }}</th>
                <th class="align-middle">{{ __('Currency') }}</th>
                <th class="align-middle">{{ __('Store') }}</th>
                <th class="align-middle">{{ __('In Store') }}</th>
                <th class="align-middle">{{ __('State') }}</th>
                <th class="align-middle">{{ __('Group') }}</th>
                <th class="align-middle"></th>
            </tr>
            </thead>
            <tbody>
            <tr data-item="order-detail" data-status="not-init" class="order-detail">
                <td class="product-name" style="width: 50%;">{!! Form::select('order_detail_add[0][product_id]', $products, [], ['class' => 'selectpicker-order-detail', 'data-item' => 'order-detail-product']) !!}</td>
                <td class="certificate-number">{!! Form::text('order_detail_add[0][certificate_number]', 0, ['class' => 'form-control']) !!}</td>
                <td class="empty-td"></td>
                <td data-item="order-detail-reference"></td>
                <td>{!! Form::text('order_detail_add[0][price]', null, ['class' => 'form-control w-auto product-price', 'size' => 5, 'data-product-price-url'=>route('presta-product.index')]) !!}</td>
                <td class="product-currency">{!! Form::select('order_detail_add[0][currency_id]', $currencies, [], ['class' => 'selectpicker-order-detail-simple']) !!}</td>
                <td class="product-store">{!! Form::select('order_detail_add[0][store_id]', $stores, [], ['class' => 'selectpicker-order-detail-simple',  'data-item' => 'order-detail-store']) !!}</td>
                <td data-item="order-detail-quantity" class="text-center align-middle"></td>
                <td class="product-state">{!! Form::select('order_detail_add[0][order_detail_state_id]', $orderDetailStartStates, [], ['class' => 'selectpicker-order-detail-simple']) !!}</td>
                <td>{!! Form::text('order_detail_add[0][printing_group]', 0, ['class' => 'form-control', 'size' => 2]) !!}</td>
                <td class="text-right" data-item="order-detail-delete">
                    {!! Form::button(__('Delete'), ['class' => 'btn', 'data-action' => 'order-detail-delete']) !!}
                </td>
            </tr>
            <tr data-item="order-detail-button">
                <td class="text-right" colspan="8">{!! Form::button(__('Add one more Product'), ['class' => 'btn', 'data-action' => 'order-detail-add']) !!}</td>
            </tr>
            </tbody>
        </table>
        <div class="col-12 text-left">
            {!! Form::button(__('Submit'), ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
        </div>
    </div>
    {!! Form::close() !!}
    <table class="hidden">
        <tr data-item="order-detail-pattern" data-status="not-init" class="order-detail">
            <td class="product-name" style="width: 50%;">{!! Form::select('order_detail_add[][product_id]', $products, [], ['class' => 'form-control selectpicker-order-detail', 'data-item' => 'order-detail-product']) !!}</td>
            <td class="certificate-number">{!! Form::text('order_detail_add[][certificate_number]', 0, ['class' => 'form-control']) !!}</td>
            <td class="empty-td"></td>
            <td data-item="order-detail-reference"></td>
            <td>{!! Form::text('order_detail_add[][price]', null, ['class' => 'form-control product-price', 'size' => 5, 'data-product-price-url'=>route('presta-product.index')]) !!}</td>
            <td class="product-currency">{!! Form::select('order_detail_add[][currency_id]', $currencies, [], ['class' => 'form-control selectpicker-order-detail-simple']) !!}</td>
            <td class="product-store">{!! Form::select('order_detail_add[][store_id]', $stores, [], ['class' => 'form-control selectpicker-order-detail-simple',  'data-item' => 'order-detail-store']) !!}</td>
            <td data-item="order-detail-quantity" class="text-center align-middle"></td>
            <td class="product-state">{!! Form::select('order_detail_add[][order_detail_state_id]', $orderDetailStartStates, [], ['class' => 'form-control selectpicker-order-detail-simple']) !!}</td>
            <td>{!! Form::text('order_detail_add[][printing_group]', 0, ['class' => 'form-control', 'size' => 2]) !!}</td>
            <td class="text-right" data-item="order-detail-delete">
                {!! Form::button(__('Delete'), ['class' => 'btn', 'data-action' => 'order-detail-delete']) !!}
            </td>
        </tr>
    </table>
    <div class="modal fade" id="pickupModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    {{ __('Do you clear the field of the point of self-delivery?') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-action="yes">{{ __('Yes') }}</button>
                    <button type="button" class="btn btn-primary" data-dismiss="modal">{{ __('No') }}</button>
                </div>
            </div>
        </div>
    </div>


@endsection