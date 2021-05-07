@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Returns Management') }}</h4>
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
        </tr>
        </thead>
        <tbody>
        @foreach ($productReturns as $productReturn)
            <tr style="background-color: {{ $productReturn->currentState()->color }};">
                <td>
                    <a href="{{ route('product-returns.edit', $productReturn) }}" class="text-dark">{{ $productReturn->id }}</a>
                </td>
                <td class="text-nowrap">
                    <div>{{ $productReturn->created_at->format('d-m-Y') }}</div>
                    <div>{{ $productReturn->created_at->format('H:i') }}</div>
                </td>
                <td>{{ $productReturn->currentState()->name }}</td>
                <td>
                    <a href="{{ route('orders.edit', $productReturn->order) }}" target="_blank" class="text-dark">{{ $productReturn->order->getDisplayNumber() }}</a>
                </td>
                <td>{{ $productReturn->order->customer->full_name }}</td>
                <td>{{ ($productReturn->carrier ? $productReturn->carrier->name : '') }}</td>
                <td class="text-nowrap">{{ $productReturn->delivery_estimated_date }}</td>
                <td>{{ $productReturn->delivery_start_time }}</td>
                <td>{{ $productReturn->delivery_end_time }}</td>
                <td>{{ $productReturn->getFullAddress() }}</td>
                <td>{{ $productReturn->comment }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $productReturns \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $productReturns->render() !!}
@endsection