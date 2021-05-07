@extends('layouts.app')

@section('content')
    {!! Form::model($order, ['method' => 'PATCH','route' => ['orders.update', $order->id]]) !!}
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4 class="float-left mr-3">{{ __('Edit Order') }} - {{ $order->getDisplayNumber() }} [{{ $order->order_number }}]
                    - {{ $order->created_at->format('d-m-Y') }}</h4>
                <div class="form-check float-left">
                    {!! Form::checkbox('is_hidden', 1, null, ['class' => 'form-check-input', 'disabled' => $order->isNewApiOrder()]) !!}
                    {!! Form::label('is_hidden', __('Hidden'), ['class' => 'form-check-label']) !!}
                </div>
                @if ($order->isNewApiOrder())
                    <div class="form-group float-left">
                        <a href="{{ route('orders.edit', ['order' => $order, 'api_edit' => 1]) }}" class="btn btn-sm btn-danger ml-3">{{ __('Start edit') }}</a>
                    </div>
                @endif
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('orders.index') }}"> {{ __('Back') }}</a>
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
    @if ($message = Session::get('success'))
        <div class="alert alert-success mt-1">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning mt-1">
            <p>{{ $message }}</p>
        </div>
    @endif
    {!! Form::hidden('updated_at_version', $order->updated_at->toDateTimeString()) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('customer_id', __('Customer:')) !!}
                <a class="ml-1" target="_blank" href="{{ route('customers.show',$order->customer->id) }}">
                    <i class="fa fa-history"></i></a><h5>{{$order->customer->full_name}}</h5>
                    {!! Form::hidden('customer_id', $order->customer->id) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('order_state_id', __('Order state:')) !!}
                {!! Form::select('order_state_id', $orderStates, $order->currentState()['id'], ['class' => 'form-control selectpicker', 'disabled' => $order->isNewApiOrder()]) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('channel_id', __('Channel:')) !!}
                {!! Form::select('channel_id', $channels, null, ['class' => 'form-control selectpicker', 'disabled' => $order->isNewApiOrder()]) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6" style="padding-top: 32px;">
            <a class="btn btn-sm btn-warning p-2 @if($order->isNewApiOrder()) disabled @endif" href="{{ route('tasks.create.from.order',$order->id) }}" target="_blank">{{ __('Create task by Order') }}</a>
            <a class="btn btn-sm btn-warning p-2 ml-3 @if($order->isNewApiOrder()) disabled @endif" href="{{ route('tasks.create.from.customer',$order->customer->id) }}" target="_blank">{{ __('Create task by Customer') }}</a>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('carrier_id', __('Carrier:')) !!}
                {!! Form::select('carrier_id', $carriers, null, ['class' => 'form-control selectpicker', 'id' => 'carrier_id', 'data-url' => route('carriers.tariff'), 'data-show-subtext' => 'true', 'disabled' => $order->isNewApiOrder()]) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-2">
            <div class="form-group" id="deliveryDate">
                {!! Form::label('date_estimated_delivery', __('Estimated delivery date:')) !!}
                {!! Form::text('date_estimated_delivery', null, ['class' => 'form-control date', 'disabled' => $order->isNewApiOrder()]) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-4">
            <div class="form-group row" id="deliveryTime">
                <div class="col-6">
                    {!! Form::label('delivery_start_time', __('Time from:')) !!}
                    {!! Form::text('delivery_start_time', null, ['class' => 'form-control time start', 'disabled' => $order->isNewApiOrder()]) !!}
                </div>
                <div class="col-6">
                    {!! Form::label('delivery_end_time', __('Time to:')) !!}
                    {!! Form::text('delivery_end_time', null, ['class' => 'form-control time end', 'disabled' => $order->isNewApiOrder()]) !!}
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group row">
                <div class="col-12">
                    {!! Form::label('delivery_shipping_number', __('Shipping number:')) !!}
                    {!! Form::text('delivery_shipping_number', null, ['class' => 'form-control', 'disabled' => $order->isNewApiOrder()]) !!}
                </div>
                <div class="col-12 mt-3">
                    {!! Form::label('comment', __('Comment:')) !!}
                    {!! Form::textarea('comment', null, ['class' => 'form-control', 'rows' => 15, 'disabled' => $order->isNewApiOrder()]) !!}
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 card mb-3">
            <div class="form-group row card-body mb-0">
                <div class="col-4">
                    {!! Form::label('delivery_post_index', __('Postal index').':') !!}
                    {!! Form::text('delivery_post_index', null, ['class' => 'form-control', 'id' => 'postal_code', 'disabled' => $order->isNewApiOrder()]) !!}
                </div>
                <div class="col-8">
                    {!! Form::label('delivery_city', __('Delivery City').':') !!}
                    {!! Form::text('delivery_city', null, ['class' => 'form-control', 'id' => 'city', 'disabled' => $order->isNewApiOrder()]) !!}
                </div>
                <div class="col-8">
                    {!! Form::label('delivery_address', __('Delivery address').':') !!}
                    {!! Form::text('delivery_address', null, ['class' => 'form-control', 'id' => 'address', 'disabled' => $order->isNewApiOrder()]) !!}
                </div>
                <div class="col-4">
                    {!! Form::label('delivery_address_flat', __('Flat').':') !!}
                    {!! Form::text('delivery_address_flat', null, ['class' => 'form-control', 'disabled' => $order->isNewApiOrder()]) !!}
                </div>
                <div class="col-12">
                    {!! Form::label('delivery_address_comment', __('Delivery Address Comment').':') !!}
                    {!! Form::textarea('delivery_address_comment', null, ['class' => 'form-control', 'rows' => 3, 'disabled' => $order->isNewApiOrder()]) !!}
                </div>
                <div class="col-12 mt-2">
                    {!! Form::label('pickup_point_code', __('Pickup point').':') !!}
                    {!! Form::select('pickup_point_code', [$order->pickup_point_code => $order->pickup_point_name], $order->pickup_point_code, ['class' => 'form-control selectpicker selectpicker-ajax-pickup-points', 'disabled' => $order->isNewApiOrder(), 'id' => 'pickup_point_code', 'data-live-search' => 'true', 'data-show-subtext' => 'true', 'data-abs-ajax-url' => route('pickups.list'), 'data-abs-locale-currently-selected' => __('Currently Selected'), 'data-abs-locale-empty-title' => __('Select and begin typing'), 'data-abs-locale-error-text' => __('Unable to retrieve results'), 'data-abs-locale-search-placeholder' => __('Search').'...', 'data-abs-locale-status-initialized' => __('Start typing a search query'), 'data-abs-locale-status-no-results' => __('No Results'), 'data-abs-locale-status-searching' => __('Searching').'...', 'data-abs-locale-status-too-short' => __('Please enter more characters')], [$order->pickup_point_code => ['data-subtext' => $order->pickup_point_address ]]) !!}
                    {!! Form::hidden('pickup_point_name', null, ['id' => 'pickup_point_name']) !!}
                    {!! Form::hidden('pickup_point_address', null, ['id' => 'pickup_point_address']) !!}
                </div>
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
            @foreach($order->orderDetails()->where('is_exchange', 0)->get() as $key => $orderDetail)
                <tr data-item="order-detail" data-status="not-init" class="order-detail">
                    <td class="product-name" style="width: 50%;">
                        {!! Form::select(
                            'order_detail['.$orderDetail->id.'][product_id]',
                            $products,
                            $orderDetail->product->id,
                            ['class' => 'selectpicker-order-detail', 'data-item' => 'order-detail-product', 'disabled' => (bool)$orderDetail->isBlockedEdit() || $order->isNewApiOrder()]
                        ) !!}
                    </td>
                    <td class="certificate-number">{!! Form::text('order_detail[' . $orderDetail->id . '][certificate_number]', $orderDetail->product->category->is_certificate ? $orderDetail->certificate->number : null, ['class' => 'form-control']) !!}</td>
                    <td class="empty-td"></td>
                    <td data-item="order-detail-reference"></td>
                    <td>
                        {!! Form::text(
                            'order_detail['.$orderDetail->id.'][price]',
                            $orderDetail->price,
                            ['class' => 'form-control w-auto', 'size'=> 8, 'disabled' => (bool)((auth()->user()->hasAnyPermission(['order-price-edit'])? !$order->isOpenEdit() || $order->isCheckedPayment() : $orderDetail->isBlockedEdit()) && !Auth::user()->hasPermissionTo('always-edit-order-detail')) || $order->isNewApiOrder()]
                        ) !!}
                    </td>
                    <td class="product-currency">
                        {!! Form::select(
                            'order_detail['.$orderDetail->id.'][currency_id]',
                            $currencies,
                            $orderDetail->currency_id,
                            ['class' => 'selectpicker-order-detail-simple', 'disabled' => (bool)(auth()->user()->hasAnyPermission(['order-price-edit'])? !$order->isOpenEdit() || $order->isCheckedPayment() : $orderDetail->isBlockedEdit()) || $order->isNewApiOrder()]
                        ) !!}
                    </td>
                    <td class="product-store">
                        {!! Form::select(
                            'order_detail['.$orderDetail->id.'][store_id]',
                            $stores,
                            $orderDetail->store_id,
                            ['class' => 'selectpicker-order-detail-simple',  'data-item' => 'order-detail-store', 'disabled' => $orderDetail->currentState()->is_block_editing_store || $order->isNewApiOrder()]
                        ) !!}
                    </td>
                    <td data-item="order-detail-quantity" class="text-center align-middle p-0"></td>
                    <td class="product-state">
                        {!! Form::select(
                            'order_detail['.$orderDetail->id.'][order_detail_state_id]',
                            $orderDetail->nextStates()->where('is_courier_state', 0)->where('is_hidden','=',0)->where('owner_type', \App\Order::class)->pluck('name', 'id')->prepend($orderDetail->currentState()['name'], $orderDetail->currentState()['id']),
                            $orderDetail->currentState()['id'],
                            ['class' => 'selectpicker-order-detail-simple', 'disabled' => $order->isNewApiOrder() || $order->isCheckedPayment()]
                        ) !!}
                    </td>
                    <td>
                        {!! Form::text(
                            'order_detail['.$orderDetail->id.'][printing_group]',
                            $orderDetail->printing_group,
                            ['class' => 'form-control', 'disabled' => $order->isNewApiOrder(), 'size' => 2]
                        ) !!}
                    </td>
                    <td class="text-right" data-item="order-detail-delete">
                        @if(!$orderDetail->isBlockedEdit() && !$order->isNewApiOrder())
                            {!! Form::button(__('Delete'), ['class' => 'btn', 'data-action' => 'order-detail-delete']) !!}
                        @else
                            {!! Form::hidden('order_detail['.$orderDetail->id.'][is_disabled]', 1) !!}
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr data-item="order-detail-button">
                <td class="text-right" colspan="8">
                    @if($order->isOpenEdit() && !$order->isNewApiOrder()){!! Form::button(__('Add one more Product'), ['class' => 'btn', 'data-action' => 'order-detail-add']) !!}@endif
                </td>
            </tr>
            </tbody>
        </table>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('certificates_id[]', __('Certificate')) !!}
                {!! Form::select('certificates_id[]', $certificates, $order->certificates()->pluck('certificate_id'), ['class' => 'form-control selectpicker', 'multiple' => true, 'id' => 'certificates_id', 'data-show-subtext' => 'true', 'disabled' => $order->isCheckedPayment()]) !!}
            </div>
            <div>
            <span class="badge badge-secondary">{{ __('Order certificate balance : ') . $order->getAllCertificateBalance() }}</span>
            <span class="badge badge-secondary">{{ __('Need to pay : ') . $order->needToPay() }}</span>
            </div>
        </div>
        @if ($order->productExchanges->isNotEmpty())
        <h4>{{ __('Exchange Order Details') }}</h4>
        <table class="table table-bordered table-sm m-0">
            <thead class="thead-light">
            <tr class="small text-center">
                <th class="align-middle">{{ __('Exchange') }}</th>
                <th class="align-middle">{{ __('Product') }}</th>
                <th class="align-middle">{{ __('Price') }}</th>
                <th class="align-middle">{{ __('Currency') }}</th>
                <th class="align-middle">{{ __('Store') }}</th>
                <th class="align-middle">{{ __('In Store') }}</th>
                <th class="align-middle">{{ __('State') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($order->orderDetails()->where('is_exchange', 1)->orderBy('product_exchange_id')->get() as $key => $orderDetail)
                <tr data-item="order-detail" data-status="not-init" class="order-detail">
                    <td>{{ $orderDetail->product_exchange_id }}</td>
                    <td class="product-name" style="width: 50%;">{!! Form::select('exchange_order_detail['.$orderDetail->id.'][product_id]', $products, $orderDetail->product->id, ['class' => 'selectpicker-order-detail', 'data-item' => 'order-detail-product', 'disabled' => true]) !!}</td>
                    <td>{!! Form::text('exchange_order_detail['.$orderDetail->id.'][price]', $orderDetail->price, ['class' => 'form-control w-auto', 'size'=> 6, 'disabled' => ($orderDetail->isBlockedEdit() && !Auth::user()->hasPermissionTo('always-edit-order-detail'))]) !!}</td>
                    <td class="product-currency">{!! Form::select('exchange_order_detail['.$orderDetail->id.'][currency_id]', $currencies, $orderDetail->currency_id, ['class' => 'selectpicker-order-detail-simple', 'disabled' => true]) !!}</td>
                    <td class="product-store">{!! Form::select('exchange_order_detail['.$orderDetail->id.'][store_id]', $stores, $orderDetail->store_id, ['class' => 'selectpicker-order-detail-simple',  'data-item' => 'order-detail-store', 'disabled' => true]) !!}</td>
                    <td data-item="order-detail-quantity" class="text-center align-middle p-0"></td>
                    <td class="product-state">{!! Form::select('exchange_order_detail['.$orderDetail->id.'][order_detail_state_id]', $orderDetail->nextStates()->where('is_hidden','=',0)->where('owner_type', \App\Order::class)->pluck('name', 'id')->prepend($orderDetail->currentState()['name'], $orderDetail->currentState()['id']), $orderDetail->currentState()['id'], ['class' => 'selectpicker-order-detail-simple', 'disabled' => true]) !!}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
        <div class="col-12 text-left mb-5 mt-5">
            @if (!$order->isNewApiOrder())
                {!! Form::button(__('Submit'), ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
            @endif
        </div>
    </div>
    {!! Form::close() !!}
    @if($order->canCloseOrder())
        {!! Form::open(['route' => 'fast.close.order','method'=>'POST', 'class' => 'form-group']) !!}
        <div class="d-flex">
            {{Form::hidden('order_id', $order->id)}}
            <div class="form-group ml-2">
                {!! Form::text('quantity', null, ['placeholder' => __('Sum'),'class' => 'form-control']) !!}
            </div>
            <div class="form-group ml-2">
                {!! Form::select('cashbox_id', $cashboxes, null,['class' => 'form-control']) !!}
            </div>
            <div class="form-group ml-2">
                {!! Form::text('comment', null, ['placeholder' => __('Comment'),'class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group ml-2">
                {!! Form::button(__('Close order'), ['type' => 'submit', 'class' => 'btn btn-success btn']) !!}
            </div>
        </div>
        {{Form::close()}}
    @endif
    <div class="row">
        @if($tasks->count())
            <div class="col-12 p-0">
                <h4 class="pl-3">{{ __('Tasks') }}</h4>
                <table class="table table-light table--responsive table-sm table-bordered table-striped small order-table">
                    <thead class="thead-light">
                    <tr>
                        <th class="align-middle text-nowrap">{{ __('Id') }}</th>
                        <th class="align-middle">{{ __('Date') }}</th>
                        <th class="align-middle">{{ __('Theme') }}</th>
                        <th class="align-middle">{{ __('Description') }}</th>
                        <th class="align-middle">{{ __('Customer') }}</th>
                        <th class="align-middle">{{ __('Order') }}</th>
                        <th class="align-middle">{{ __('Performer') }}</th>
                        <th class="align-middle">{{ __('State') }}</th>
                        <th class="align-middle"> {{ __('Author') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($tasks as $task)
                        <tr style="background-color: {{ $task->currentState()['color'] }};">
                            <td>
                                <a href="{{ route('tasks.edit',$task->id) }}" target="_blank">{{ $task->id }}</a>
                            </td>
                            <td class="text-nowrap">
                                <div>{{ $task->created_at->format('d-m-Y') }}</div>
                                <div>{{ $task->created_at->format('H:i') }}</div>
                            </td>
                            <td>{{ $task->name }}</td>
                            <td>{{ $task->description }}</td>
                            <td class="text-nowrap">{{ $task->customer->full_name }}</td>
                            <td>{{ $task->order->getDisplayNumber() }}</td>
                            <td>{{ $task->performer->name }}</td>
                            <td class="text-nowrap">{{ $task->currentState() ? $task->currentState()->name : ''}}</td>
                            <td>{{ is_null($task->author) ? __('System') : $task->author->name }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($cdekStates->count())
                <div class="col-12 p-0">
                    <h4 class="pl-3">{{ __('CDEK states') }}</h4>
                    <table class="table table-light table--responsive table-sm table-bordered table-striped small order-table">
                        <thead class="thead-light">
                        <tr>
                            <th class="align-middle">{{ __('Date') }}</th>
                            <th class="align-middle">{{ __('Name') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($cdekStates as $state)
                            <tr>
                                <td class="text-nowrap">
                                    <div>{{ $state->pivot->created_at->format('d-m-Y') }}</div>
                                    <div>{{ $state->pivot->created_at->format('H:i') }}</div>
                                </td>
                                <td>{{ $state->name }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
        @endif
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
        @if($isAvailableReturn || $order->productReturns->isNotEmpty())
            <div class="col-12 p-0 mb-3">
                <h4 class="pl-3">{{ __('Returns') }}</h4>
                @if($order->productReturns->isNotEmpty())
                    <table class="table table-light table-bordered">
                        <thead class="thead-light">
                        <tr>
                            <th>{{ __('Id') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('State') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            {{
                            /**
                              * @var $productReturn \App\ProductReturn
                              **/
                            }}
                        @endphp
                        @foreach($order->productReturns as $productReturn)
                            <tr>
                                <td>{{ $productReturn->id }}</td>
                                <td>{{ $productReturn->created_at }}</td>
                                <td>{{ $productReturn->currentState()->name }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
                @if($isAvailableReturn)
                    <div class="col-12">
                        <a href="{{ route('product-returns.create', $order) }}" class="btn btn-secondary">{{ __('Create Return') }}</a>
                    </div>
                @endif
            </div>
        @endif
        @if($isAvailableReturn || $order->productExchanges->isNotEmpty())
            <div class="col-12 p-0 mb-3">
                <h4 class="pl-3">{{ __('Exchanges') }}</h4>
                @if($order->productExchanges->isNotEmpty())
                    <table class="table table-light table-bordered">
                        <thead class="thead-light">
                        <tr>
                            <th>{{ __('Id') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('State') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            {{
                            /**
                              * @var $productExchange \App\ProductExchange
                              **/
                            }}
                        @endphp
                        @foreach($order->productExchanges as $productExchange)
                            <tr>
                                <td>{{ $productExchange->id }}</td>
                                <td>{{ $productExchange->created_at }}</td>
                                <td>{{ $productExchange->currentState()->name }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
                @if($isAvailableReturn)
                    <div class="col-12">
                        <a href="{{ route('product-exchanges.create', $order) }}" class="btn btn-secondary">{{ __('Create Exchange') }}</a>
                    </div>
                @endif
            </div>
        @endif

        @can('fast-message-send-sms')
            <div class="col-md-6">
                <h4>{{__('Fast sms messages')}}</h4>
            <div id="fast-message-sms" data-message-templates="{{$smsData}}" data-message-url="{{route('fast.message.send')}}" data-user-destination="{{$order->customer->phone}}" data-order_id="{{$order->id}}"></div>
            </div>
        @endcan
        @can('fast-message-send-email')
            <div class="col-md-6">
                <h4>{{__('Fast email messages')}}</h4>
            </div>
        @endcan
        @if($orderHistory->count())
            <div class="col-12 p-0">
                <h4 class="pl-3">{{ __('History') }}</h4>
                <table class="table table-light table-bordered table-sm">
                    <thead class="thead-light">
                    <tr>
                        <th>{{ __('Time') }}</th>
                        <th>{{ __('Author') }}</th>
                        <th>{{ __('Comment') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($orderHistory as $historyItem)
                        <tr>
                            <td style="width: 50px;" class="text-nowrap">{{ $historyItem->created_at->format('d-m-Y H:i') }}</td>
                            <td style="width: 50px;" class="text-nowrap">
                                @if($historyItem->is_call)
                                    <i class="fa fa-phone-square mr-1"></i>
                                @endif
                                {{ $historyItem->author }}
                            </td>
                            <td>
                                @if($historyItem->is_call)
                                    @if($historyItem->comment->recordUrl && $historyItem->comment->recordUrl != '')
                                        <div class="pcast-player">
                                            <div class="pcast-player-controls">
                                                <button class="pcast-play">
                                                    <i class="fa fa-play"></i><span>{{ __('Play') }}</span></button>
                                                <button class="pcast-pause">
                                                    <i class="fa fa-pause"></i><span>{{ __('Pause') }}</span></button>
                                                <button class="pcast-rewind">
                                                    <i class="fa fa-fast-backward"></i><span>{{ __('Rewind') }}</span>
                                                </button>
                                                <span class="pcast-currenttime pcast-time">00:00</span>
                                                <progress class="pcast-progress" value="0"></progress>
                                                <span class="pcast-duration pcast-time">00:00</span>
                                                <button class="pcast-speed">1x</button>
                                                <button class="pcast-mute">
                                                    <i class="fa fa-volume-up"></i><span>{{ __('Mute\Unmute') }}</span>
                                                </button>
                                            </div>
                                            <audio preload="auto" src="{{ route('records.call', $historyItem->comment) }}"></audio>
                                        </div>
                                    @endif
                                @else
                                    @if($historyItem->is_task)
                                        {!! preg_replace("/#task-[0-9]+/", "<a href=\"".route('tasks.edit', $historyItem->task_id)."\" target=\"_blank\">".$historyItem->task_id."</a>", $historyItem->comment) !!}
                                    @else
                                        {!! $historyItem->comment !!}
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        {!! Form::open(['route' => ['orders.comment.add', $order],'method'=>'POST', 'class' => 'col-12 p-0' ]) !!}
        <div>
            <div class="form-group">
                {!! Form::textarea('comment', null, ['placeholder' => __('Comment'),'class' => 'form-control mt-2', 'required', 'rows' => 3, 'disabled' => $order->isNewApiOrder()]) !!}
                {!! Form::hidden('order_id', $order->id) !!}
            </div>
        </div>
        <div class="text-right">
            <button type="submit" class="btn btn-info btn-sm" @if($order->isNewApiOrder()) disabled @endif >{{ __('Add comment') }}</button>
        </div>
        <div>
            @if($order->clientID)
                <span class="badge badge-secondary">{{ $order->clientID }}</span>
            @endif
            @if($order->utm_campaign)
                <span class="badge badge-secondary">{{ $order->utm_campaign }}</span>
            @endif
            @if($order->utm_source)
                <span class="badge badge-secondary">{{ $order->utm_source }}</span>
            @endif
            @if($order->gaClientID)
                <span class="badge badge-secondary">{{ $order->gaClientID }}</span>
            @endif
            @if($order->search_query)
                    <span class="badge badge-secondary">{{ $order->search_query }}</span>
                @endif
        </div>
            @if(!empty($courier))
                <div>
                    <h4 class="pl-3">{{ __('Courier') }} : {{$courier}}</h4>
                </div>
            @endif
            @can('expense-setting')
            <div>
                <h4 class="pl-3">{{ __('Order expense') }}</h4>
                @foreach($expenses as $expense)
                    <span class="badge badge-secondary">{{ $expense['name'] }}</span>
                    <span class="badge badge-secondary">{{ $expense['summ'] }}</span>
                    <br>
                    @endforeach

            </div>
            @endcan
            {!! Form::close() !!}
    </div>
    <table class="hidden">
    <tr data-item="order-detail-pattern" data-status="not-init" class="order-detail">
            <td class="product-name" style="width: 50%;">{!! Form::select('order_detail_add[][product_id]', $products, [], ['class' => 'form-control selectpicker-order-detail', 'data-item' => 'order-detail-product']) !!}</td>
            <td class="certificate-number">{!! Form::text('order_detail_add[][certificate_number]', 0, ['class' => 'form-control']) !!}</td>
            <td class="empty-td"></td>
            <td data-item="order-detail-reference"></td>
            <td>{!! Form::text('order_detail_add[][price]', null, ['class' => 'form-control', 'size' => 5]) !!}</td>
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
