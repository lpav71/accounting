@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Show Order') }}</h4>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('orders.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <strong> {{ __('Customer:') }}</strong>
                {{ $order->customer->full_name }}
            </div>
        </div>
        <table class="table table-light table-bordered table-responsive-sm">
            <thead class="thead-light">
            <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Date') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($orderStates as $state)
                <tr>
                    <td>{{ $state['name'] }}</td>
                    <td>{{ $state['pivot']['created_at'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="col-12">
            <div class="form-group">
                <strong> {{ __('Channel:') }}</strong>
                {{ $order->channel->name }}
            </div>
            <div class="form-group">
                <strong> {{ __('Employee:') }}</strong>
                @if($order->user) {{ $order->user->name }} @endif
            </div>
            <div class="form-group">
                <strong> {{ __('Carrier:') }}</strong>
                @if($order->carrier) {{ $order->carrier->name }} @endif
            </div>
            <div class="form-group">
                <strong> {{ __('Estimated delivery date:') }}</strong>
                {{ $order->date_estimated_delivery }} :: {{ $order->delivery_start_time }}
                - {{ $order->delivery_end_time }}
            </div>
            <div class="form-group">
                <strong> {{ __('Shipping number:') }}</strong>
                {{ $order->delivery_shipping_number }}
            </div>
            <div class="form-group">
                <strong> {{ __('Delivery City:') }}</strong>
                {{ $order->delivery_city }}
            </div>
            <div class="form-group">
                <strong> {{ __('Delivery Address:') }}</strong>
                {{ $order->delivery_address }}
            </div>
        </div>
        <table class="table table-light table-bordered table-responsive-sm">
            <thead class="thead-light">
            <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Price') }}</th>
                <th>{{ __('States') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($order->orderDetails as $key => $orderDetail)
                <tr>
                    <td>{{ $orderDetail->product->name }}</td>
                    <td>{{ $orderDetail->price }}</td>
                    <td>
                        @if(!empty($orderDetail->states()))
                            <ul>
                            @foreach($orderDetail->states as $v)
                                <li>{{ $v->pivot->created_at }}: <label class="badge badge-success">{{ $v->name }}</label></li>
                            @endforeach
                            </ul>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection