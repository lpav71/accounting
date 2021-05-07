@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Exchange States Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('orderState-create')
                    <a class="btn btn-sm btn-success" href="{{ route('product-exchange-states.create') }}"> {{ __('Create New Exchange State') }}</a>
                @endcan
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
    <table class="table table-light table-bordered table-responsive-sm table-sm">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Previous state') }}</th>
            <th>{{ __('Check payment') }}</th>
            <th>{{ __('Color') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($productExchangeStates as $productExchangeState)
            <tr>
                <td>
                    @can('orderState-edit')
                        <a href="{{ route('product-exchange-states.edit', ['product_exchange_id' => $productExchangeState->id]) }}">
                            {{ $productExchangeState->id }}
                        </a>
                    @else
                        {{ $productExchangeState->id }}
                    @endcan
                </td>
                <td>@can('orderState-edit')
                        <a href="{{ route('product-exchange-states.edit', ['product_exchange_id' => $productExchangeState->id]) }}">
                            {{ $productExchangeState->name }}
                        </a>
                    @else
                        {{ $productExchangeState->name }}
                    @endcan</td>
                <td>
                    @foreach($productExchangeState->previousStates as $previousState)
                        <label class="badge badge-success">{{ $previousState->name }}</label>
                    @endforeach
                </td>
                <td class="text-center">@include('_common.icons.controls.isActive', ['isActive' => $productExchangeState->check_payment])</td>
                <td class="text-center" style="background-color: {{ $productExchangeState->color }};">
                    {{ $productExchangeState->color }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $productExchangeStates \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $productExchangeStates->render() !!}
@endsection