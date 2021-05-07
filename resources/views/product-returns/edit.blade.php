@extends('layouts.app')


@section('content')
    {!! Form::model($productReturn, ['method' => 'PATCH','route' => ['product-returns.update', $productReturn]]) !!}
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4 class="float-left mr-3">{{ __('Edit Return to Order') }} {{ $order->getDisplayNumber() }}</h4>
                [ {{ $order->customer->full_name }} ]
            </div>
        </div>
    </div>


    @if (count($errors) > 0)
        <div class="alert alert-danger mt-1">
            <strong>{{ __('Whoops!') }}</strong> {{ __('There were some problems with your input.') }}<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {!! Form::hidden('updated_at_version', $productReturn->updated_at->toDateTimeString()) !!}
    {!! Form::hidden('order_id', $order->id) !!}

    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('product_return_state_id', __('State:')) !!}
                {!! Form::select('product_return_state_id', $states, null, ['class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('carrier_id', __('Carrier:')) !!}
                {!! Form::select('carrier_id', $carriers, null, ['class' => 'form-control selectpicker', 'id' => 'carrier_id', 'data-url' => route('carriers.tariff'), 'data-show-subtext' => 'true']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('delivery_shipping_number', __('Shipping number:')) !!}
                {!! Form::text('delivery_shipping_number', null, ['class' => 'form-control']) !!}
            </div>
            <div>
                {!! Form::label('comment', __('Comment:')) !!}
                {!! Form::textarea('comment', null, ['class' => 'form-control', 'rows' => 7]) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group row">
                <div class="col-xs-12 col-sm-4" id="deliveryDate">
                    {!! Form::label('delivery_estimated_date', __('Estimated pick up Date:')) !!}
                    {!! Form::text('delivery_estimated_date', null, ['class' => 'form-control date', 'autocomplete' => 'some-off']) !!}
                </div>
                <div class="col-xs-12 col-sm-8 d-flex p-0" id="deliveryTime">
                    <div class="col-6">
                        {!! Form::label('delivery_start_time', __('Time from:')) !!}
                        {!! Form::text('delivery_start_time', null, ['class' => 'form-control time start', 'autocomplete' => 'some-off']) !!}
                    </div>
                    <div class="col-6">
                        {!! Form::label('delivery_end_time', __('Time to:')) !!}
                        {!! Form::text('delivery_end_time', null, ['class' => 'form-control time end', 'autocomplete' => 'some-off']) !!}
                    </div>
                </div>
            </div>
            <div class="border rounded bg-white p-3">
                <div class="row">
                    <div class="col-4 mb-2">
                        {!! Form::label('delivery_post_index', __('Postal index:')) !!}
                        {!! Form::text('delivery_post_index', null, ['class' => 'form-control', 'id' => 'postal_code', 'autocomplete' => 'some-off']) !!}
                    </div>
                    <div class="col-8 mb-2">
                        {!! Form::label('delivery_city', __('Pick up City:')) !!}
                        {!! Form::text('delivery_city', null, ['class' => 'form-control', 'id' => 'city', 'autocomplete' => 'some-off']) !!}
                    </div>
                    <div class="col-8 mb-2">
                        {!! Form::label('delivery_address', __('Pick up address:')) !!}
                        {!! Form::text('delivery_address', null, ['class' => 'form-control', 'id' => 'address', 'autocomplete' => 'some-off']) !!}
                    </div>
                    <div class="col-4 mb-2">
                        {!! Form::label('delivery_flat', __('Flat:')) !!}
                        {!! Form::text('delivery_flat', null, ['class' => 'form-control', 'autocomplete' => 'some-off']) !!}
                    </div>
                    <div class="col-12">
                        {!! Form::label('delivery_comment', __('Pick up Address Comment:')) !!}
                        {!! Form::textarea('delivery_comment', null, ['class' => 'form-control', 'rows' => 10, 'autocomplete' => 'some-off']) !!}
                    </div>
                </div>
            </div>
        </div>
        @php
            {{
            /**
              * @var $orderDetail \App\OrderDetail
              **/
            }}
        @endphp
        <table class="table table-bordered table-sm m-3">
            <thead class="thead-light">
            <tr class="small text-center">
                <th class="align-middle">{{ __('Product') }}</th>
                <th class="align-middle">{{ __('Reference') }}</th>
                <th class="align-middle">{{ __('Price') }}</th>
                <th class="align-middle">{{ __('Currency') }}</th>
                <th class="align-middle">{{ __('Store') }}</th>
                <th class="align-middle">{{ __('State') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($orderDetails as $orderDetail)
                <tr data-item="order-detail" data-status="not-init" class="order-detail">
                    <td class="product-name" style="width: 50%;">
                        {!! Form::select(
                            'order_detail['.$orderDetail->id.'][product_id]',
                            [$orderDetail->product->id => $orderDetail->product->name],
                            $orderDetail->product->id,
                            ['class' => 'selectpicker-order-detail', 'data-item' => 'order-detail-product', 'disabled' => true]
                        ) !!}
                    </td>
                    <td data-item="order-detail-reference"></td>
                    <td>
                        {!! Form::text(
                        'order_detail['.$orderDetail->id.'][price]',
                        $orderDetail->price,
                        ['class' => 'form-control', 'size'=> 6, 'disabled' => !Auth::user()->hasPermissionTo('always-edit-order-detail')]
                        ) !!}
                    </td>
                    <td class="product-currency">
                        {!! Form::select(
                        'order_detail['.$orderDetail->id.'][currency_id]',
                        [$orderDetail->currency->id => $orderDetail->currency->name],
                        $orderDetail->currency->id,
                        ['class' => 'selectpicker-order-detail', 'disabled' => true]
                        ) !!}
                    </td>
                    <td class="product-store">
                        {!! Form::select(
                        'order_detail['.$orderDetail->id.'][store_id]',
                        $stores,
                        $orderDetail->store_id,
                        ['class' => 'selectpicker-order-detail-simple',  'data-item' => 'order-detail-store', 'disabled' => $orderDetail->currentState()->is_block_editing_order_detail]
                        ) !!}
                    </td>
                    <td class="product-state">
                        {!! Form::select(
                        'order_detail['.$orderDetail->id.'][order_detail_state_id]',
                        $orderDetail->nextStates()->where('is_hidden','=',0)->where('owner_type', \App\ProductReturn::class)->pluck('name', 'id')->prepend($orderDetail->currentState()['name'], $orderDetail->currentState()['id']),
                        $orderDetail->currentState()['id'],
                        ['class' => 'selectpicker-order-detail-simple', 'disabled' => $orderDetail->currentState()->is_block_editing_order_detail]
                        ) !!}
                        @if($orderDetail->currentState()->is_block_editing_order_detail)
                            {!! Form::hidden('order_detail['.$orderDetail->id.'][order_detail_state_id]', $orderDetail->currentState()['id']) !!}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if($cashboxOperations->count())
            <div class="col-12 p-0">
                <h4 class="pl-3">{{ __('Cashbox operations') }}</h4>
                <table class="table table-light table-bordered">
                    <thead class="thead-light">
                    <tr>
                        <th>{{ __('Cashbox') }}</th>
                        <th>{{ __('Id') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Currency') }}</th>
                        <th>{{ __('Sum') }}</th>
                        <th>{{ __('Comment') }}</th>
                        <th>{{ __('Order') }}</th>
                        <th>{{ __('User') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($cashboxOperations as $key => $operation)
                        <tr>
                            <td>{{ $operation->storage->name }}</td>
                            <td>{{ $operation->id }}</td>
                            <td>{{ $operation->created_at }}</td>
                            <td>{{ ($operation->type == 'C' ? __('Credit') : __('Debit')) }}</td>
                            <td>{{ $operation->operable->name }}</td>
                            <td>{{ $operation->quantity }}</td>
                            <td>{{ $operation->comment }}</td>
                            <td>{{ $operation->order ? $operation->order->getDisplayNumber() : '' }}</td>
                            <td>{{ $operation->user->name }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="col-12 text-left">
            {!! Form::button(__('Save'), ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
        </div>
    </div>
    {!! Form::close() !!}
@endsection