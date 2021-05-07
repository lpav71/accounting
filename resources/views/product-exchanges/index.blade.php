@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Exchanges Management') }}</h4>
            </div>
        </div>
    </div>
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning">
            <p>{{ $message }}</p>
        </div>
    @endif
    <table class="table table-light table--responsive table-sm table-bordered table-striped small-1 order-table">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('State') }}</th>
            <th>{{ __('Order') }}</th>
            <th>{{ __('Customer') }}</th>
            <th>{{ __('Carrier') }}</th>
            <th>{{ __('Deliv.') }}</th>
            <th>{{ __('St.') }}</th>
            <th>{{ __('En.') }}</th>
            <th>{{ __('Address') }}</th>
            <th>{{ __('Comment') }}</th>
            <th>{{ __('Information') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($productExchanges as $productExchange)
            <tr style="background-color: {{ $productExchange->currentState()->color }};">
                <td>
                    <a href="{{ route('product-exchanges.edit', $productExchange) }}" class="text-dark">{{ $productExchange->id }}</a>
                </td>
                <td class="text-nowrap">
                    <div>{{ $productExchange->created_at->format('d-m-Y') }}</div>
                    <div>{{ $productExchange->created_at->format('H:i') }}</div>
                </td>
                <td>{{ $productExchange->currentState()->name }}</td>
                <td>
                    <a href="{{ route('orders.edit', $productExchange->order) }}" target="_blank" class="text-dark">{{ $productExchange->order->getDisplayNumber() }}</a>
                </td>
                <td>{{ $productExchange->order->customer->full_name }}</td>
                <td>{{ ($productExchange->carrier ? $productExchange->carrier->name : '') }}</td>
                <td class="text-nowrap">{{ $productExchange->delivery_estimated_date }}</td>
                <td>{{ $productExchange->delivery_start_time }}</td>
                <td>{{ $productExchange->delivery_end_time }}</td>
                <td>{{ $productExchange->getFullAddress() }}</td>
                <td>{{ $productExchange->comment }}</td>
                <td class="not-head text-center">
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('product-exchanges.pdf.get',$productExchange->id) }}">
                        <i class="fa fa-print fa-2x"></i>
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $productExchanges \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $productExchanges->render() !!}
@endsection