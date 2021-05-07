@extends('layouts.app')


@section('content')
    {!! Form::model($productExchange, ['route' => ['product-exchanges.update', $productExchange],'method'=>'PATCH']) !!}
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4 class="float-left mr-3">{{ __('Edit Exchange to Order') }} {{ $order->getDisplayNumber() }}</h4>
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

    {!! Form::hidden('updated_at_version', $productExchange->updated_at->toDateTimeString()) !!}
    {!! Form::hidden('order_id', $order->id) !!}

    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('product_exchange_state_id', __('State:')) !!}
                {!! Form::select('product_exchange_state_id', $states, null, ['class' => 'form-control selectpicker']) !!}
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
                        ['class' => 'selectpicker-order-detail-simple',  'data-item' => 'order-detail-store', 'disabled' => ($orderDetail->currentState()->is_block_editing_order_detail && !Auth::user()->hasPermissionTo('always-edit-order-detail'))]
                        ) !!}
                    </td>
                    <td class="product-state">
                        {!! Form::select(
                        'order_detail['.$orderDetail->id.'][order_detail_state_id]',
                        $orderDetail->nextStates()->where('is_hidden','=',0)->where('owner_type', \App\ProductExchange::class)->pluck('name', 'id')->prepend($orderDetail->currentState()['name'], $orderDetail->currentState()['id']),
                        $orderDetail->currentState()['id'],
                        ['class' => 'selectpicker-order-detail-simple', 'disabled' => $orderDetail->currentState()->is_block_editing_order_detail]
                        ) !!}
                        @if($orderDetail->currentState()->is_block_editing_order_detail)
                        {!! Form::hidden('order_detail['.$orderDetail->id.'][order_detail_state_id]', $orderDetail->currentState()['id'] ) !!}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <h4 class="m-3">{{ __('Exchange Order Details') }}</h4>
        <table class="table table-bordered table-sm m-3 not-hidden">
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
            @foreach($exchangeOrderDetails as $orderDetail)
                <tr data-item="order-detail" data-status="not-init" class="order-detail">
                    <td class="product-name" style="width: 50%;">
                        {!! Form::select(
                        "exchange_order_detail[{$orderDetail->id}][product_id]",
                        $products,
                        $orderDetail->product->id,
                        [
                        'class' => 'selectpicker-order-detail',
                        'data-item' => 'order-detail-product',
                        'disabled' => $orderDetail->isBlockedEditExchange()
                        ]
                        ) !!}
                    </td>
                    <td class="certificate-number">{!! Form::text('exchange_order_detail[' . $orderDetail->id . '][certificate_number]', $orderDetail->product->category->is_certificate ? $orderDetail->certificate->number : 0, ['class' => 'form-control']) !!}</td>
                    <td class="empty-td"></td>
                    <td data-item="order-detail-reference"></td>
                    <td>
                        {!! Form::text(
                        "exchange_order_detail[{$orderDetail->id}][price]",
                        $orderDetail->price,
                        [
                        'class' => 'form-control',
                        'size' => 5,
                        'disabled' => (bool)(auth()->user()->hasAnyPermission(['order-price-edit'])? !$productExchange->isOpenEdit() || $productExchange->isCheckedPayment() : ($orderDetail->isBlockedEditExchange() && !Auth::user()->hasPermissionTo('always-edit-order-detail')))
                        ]
                        ) !!}
                    </td>
                    <td class="product-currency">
                        {!! Form::select(
                        "exchange_order_detail[{$orderDetail->id}][currency_id]",
                        $currencies,
                        $orderDetail->currency_id,
                        [
                        'class' => 'selectpicker-order-detail-simple',
                        'disabled' => (bool)(auth()->user()->hasAnyPermission(['order-price-edit'])? !$productExchange->isOpenEdit() || $productExchange->isCheckedPayment(): $orderDetail->isBlockedEditExchange())
                        ]
                        )!!}
                    </td>
                    <td class="product-store">
                        {!! Form::select(
                        "exchange_order_detail[{$orderDetail->id}][store_id]",
                        $stores,
                        $orderDetail->store_id,
                        [
                        'class' => 'selectpicker-order-detail-simple',
                        'data-item' => 'order-detail-store',
                        'disabled' => $orderDetail->currentState()->is_block_editing_order_detail
                        ]
                        ) !!}
                    </td>
                    <td data-item="order-detail-quantity" class="text-center align-middle"></td>
                    <td class="product-state">
                        {!! Form::select(
                        "exchange_order_detail[{$orderDetail->id}][order_detail_state_id]",
                        $orderDetail->nextStates()->where('is_hidden','=',0)->where('owner_type', \App\ProductExchange::class)->pluck('name', 'id')->prepend($orderDetail->currentState()->name, $orderDetail->currentState()->id),
                        $orderDetail->currentState()->id,
                        [
                        'class' => 'selectpicker-order-detail-simple',
                        'disabled' => $productExchange->isCheckedPayment()
                        ]
                        ) !!}
                    </td>
                    <td>
                        {!! Form::text(
                            'exchange_order_detail['.$orderDetail->id.'][printing_group]',
                            $orderDetail->printing_group,
                            ['class' => 'form-control', 'size' => 2]
                        ) !!}
                    </td>
                    <td class="text-right" data-item="order-detail-delete">
                        @if(!$orderDetail->isBlockedEditExchange())
                            {!! Form::button(__('Delete'), ['class' => 'btn', 'data-action' => 'order-detail-delete']) !!}
                        @else
                            {!! Form::hidden("exchange_order_detail[{$orderDetail->id}][is_disabled]", 1) !!}
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr data-item="order-detail-button">
                <td class="text-right" colspan="7">
                    @if($productExchange->isOpenEdit())
                        {!! Form::button(__('Add one more Product'), ['class' => 'btn', 'data-action' => 'order-detail-add']) !!}
                    @endif
                </td>
            </tr>
            </tbody>
        </table>
        <div class="col-12 text-left">
            {!! Form::button(__('Save'), ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
        </div>
    </div>
    {!! Form::close() !!}
    @if($productExchange->isOpenEdit())
        <table class="hidden">
            <tr data-item="order-detail-pattern" data-status="not-init" class="order-detail">
                <td class="product-name" style="width: 50%;">
                    {!! Form::select(
                    'order_detail_add[][product_id]',
                    $products,
                    [],
                    ['class' => 'form-control selectpicker-order-detail', 'data-item' => 'order-detail-product']
                    ) !!}
                </td>
                <td class="certificate-number">{!! Form::text('order_detail_add[][certificate_number]', 0, ['class' => 'form-control']) !!}</td>
                <td class="empty-td"></td>
                <td data-item="order-detail-reference"></td>
                <td>
                    {!! Form::text(
                    'order_detail_add[][price]',
                    null,
                    ['class' => 'form-control', 'size' => 5]
                    ) !!}
                </td>
                <td class="product-currency">
                    {!! Form::select(
                    'order_detail_add[][currency_id]',
                    $currencies,
                    [],
                    ['class' => 'form-control selectpicker-order-detail-simple']
                    ) !!}
                </td>
                <td class="product-store">
                    {!! Form::select(
                    'order_detail_add[][store_id]',
                    $stores,
                    [],
                    ['class' => 'form-control selectpicker-order-detail-simple',  'data-item' => 'order-detail-store']
                    ) !!}
                </td>
                <td data-item="order-detail-quantity" class="text-center align-middle"></td>
                <td class="product-state">
                    {!! Form::select(
                    'order_detail_add[][order_detail_state_id]',
                    $orderDetailStartStates,
                    [],
                    ['class' => 'form-control selectpicker-order-detail-simple']
                    ) !!}
                </td>
                <td>{!! Form::text('order_detail_add[][printing_group]', 0, ['class' => 'form-control', 'size' => 2]) !!}</td>          
                <td class="text-right" data-item="order-detail-delete">
                    {!! Form::button(__('Delete'), ['class' => 'btn', 'data-action' => 'order-detail-delete']) !!}
                </td>
            </tr>
        </table>
    @endif
@endsection