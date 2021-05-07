@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('CDEK report') }}</h2>
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
    <div class="col-12 p-0" id="analytics">
        {!! Form::open(['route' => 'analytics.report.cdek.delivery','method'=>'GET', 'class' => 'form-group']) !!}
        <div class="d-flex">
            <div>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        {!! Form::label('from', __('From'), ['class' => 'form-control form-control-sm m-0']) !!}
                    </div>
                    <div class="input-group-append">
                        {!! Form::text('from', $dateFrom, ['class' => 'form-control form-control-sm date']) !!}
                    </div>
                </div>
            </div>
            <div>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        {!! Form::label('to', __('To'), ['class' => 'form-control form-control-sm m-0']) !!}
                    </div>
                    <div class="input-group-append">
                        {!! Form::text('to', $dateTo, ['class' => 'form-control form-control-sm date']) !!}
                    </div>
                </div>
            </div>
            @can('analytics-products-list'){!! Form::submit(__('Save default'), ['class' => 'btn btn-sm pull-right col-sm-auto col-xs-12 mt-2 mt-sm-0 ml-0 ml-sm-5', 'name' => 'save']) !!}@endcan
        </div>
        {!! Form::hidden('report', 1) !!}
        {!! Form::submit(__('Show report'), ['class' => 'btn btn-sm m-0 mt-2', 'name' => 'submit']) !!}
        {!! Form::close() !!}
    </div>
    <table class="table table-light table-bordered table-sm">
        <thead class="thead-light">
        <tr>
            <th rowspan="2">{{ __('Date') }}</th>
            <th rowspan="2">{{ __('CashOnDeliv') }}</th>
            <th rowspan="2">{{ __('CashOnDelivFact') }}</th>
            <th rowspan="2">{{ __('PackageDeliverySum') }}</th>
            <th rowspan="2">{{ __('PackageServiceSum') }}</th>
            <th rowspan="2">{{ __('Cash') }}</th>
            <th colspan="3">{{ __('CashOnDelivFactRetail') }}</th>
            <th colspan="3">{{ __('ExpectedSumRetail') }}</th>
            <th colspan="3">{{ __('RetailAllQuantity') }}</th>
            <th colspan="3">{{ __('RetailFactQuantity') }}</th>
        </tr>
        <tr>
            <th class="bg-info">{{ __('All') }}</th>
            <th>{{ __('PVZ') }}</th>
            <th>{{ __('Courier') }}</th>
            <th class="bg-info">{{ __('All') }}</th>
            <th>{{ __('PVZ') }}</th>
            <th>{{ __('Courier') }}</th>
            <th class="bg-info">{{ __('All') }}</th>
            <th>{{ __('PVZ') }}</th>
            <th>{{ __('Courier') }}</th>
            <th class="bg-info">{{ __('All') }}</th>
            <th>{{ __('PVZ') }}</th>
            <th>{{ __('Courier') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['groups'] as $groupName => $group)
            <tr class="font-italic bg-secondary">
                <td colspan="18">
                    {{ $groupName }}
                </td>
            </tr>
            @if(isset($group['rows']))
                @foreach ($group['rows'] as $rowName => $row)
                    <tr>
                        <td>{{ $rowName }}</td>
                        <td>{{ $row['CashOnDeliv'] }}</td>
                        <td>{{ $row['CashOnDelivFact'] }}</td>
                        <td>{{ $row['PackageDeliverySum'] }}</td>
                        <td>{{ $row['PackageServiceSum'] }}</td>
                        <td>{{ $row['Cash'] }}</td>
                        <td class="bg-info">{{ $row['CashOnDelivFactRetail'] }}</td>
                        <td>{{ $row['CashOnDelivFactRetailPVZ'] }}</td>
                        <td>{{ $row['CashOnDelivFactRetailCourier'] }}</td>
                        <td class="bg-info">{{ $row['ExpectedSum'] }}</td>
                        <td>{{ $row['ExpectedSumPVZ'] }}</td>
                        <td>{{ $row['ExpectedSumCourier'] }}</td>
                        <td class="bg-info">{{ $row['RetailAllQuantity'] }}</td>
                        <td>{{ $row['RetailAllQuantityPVZ'] }}</td>
                        <td>{{ $row['RetailAllQuantityCourier'] }}</td>
                        <td class="bg-info">{{ $row['RetailFactQuantity'] }}</td>
                        <td>{{ $row['RetailFactQuantityPVZ'] }}</td>
                        <td>{{ $row['RetailFactQuantityCourier'] }}</td>
                    </tr>
                @endforeach
            @endif
            @foreach ($group['total'] as $rowName => $row)
                <tr class="font-weight-bold">
                    <td>{{ $rowName }}</td>
                    <td>{{ $row['CashOnDeliv'] }}</td>
                    <td>{{ $row['CashOnDelivFact'] }}</td>
                    <td>{{ $row['PackageDeliverySum'] }}</td>
                    <td>{{ $row['PackageServiceSum'] }}</td>
                    <td>{{ $row['Cash'] }}</td>
                    <td class="bg-info">{{ $row['CashOnDelivFactRetail'] }}</td>
                    <td>{{ $row['CashOnDelivFactRetailPVZ'] }}</td>
                    <td>{{ $row['CashOnDelivFactRetailCourier'] }}</td>
                    <td class="bg-info">{{ $row['ExpectedSum'] }}</td>
                    <td>{{ $row['ExpectedSumPVZ'] }}</td>
                    <td>{{ $row['ExpectedSumCourier'] }}</td>
                    <td class="bg-info">{{ $row['RetailAllQuantity'] }}</td>
                    <td>{{ $row['RetailAllQuantityPVZ'] }}</td>
                    <td>{{ $row['RetailAllQuantityCourier'] }}</td>
                    <td class="bg-info">{{ $row['RetailFactQuantity'] }}</td>
                    <td>{{ $row['RetailFactQuantityPVZ'] }}</td>
                    <td>{{ $row['RetailFactQuantityCourier'] }}</td>
                </tr>
            @endforeach
        @endforeach
        @foreach ($data['total_rows'] as $rowName => $row)
            <tr class="font-weight-bold bg-info">
                <td>{{ $rowName }}</td>
                <td>{{ $row['CashOnDeliv'] }}</td>
                <td>{{ $row['CashOnDelivFact'] }}</td>
                <td>{{ $row['PackageDeliverySum'] }}</td>
                <td>{{ $row['PackageServiceSum'] }}</td>
                <td>{{ $row['Cash'] }}</td>
                <td>{{ $row['CashOnDelivFactRetail'] }}</td>
                <td>{{ $row['CashOnDelivFactRetailPVZ'] }}</td>
                <td>{{ $row['CashOnDelivFactRetailCourier'] }}</td>
                <td>{{ $row['ExpectedSum'] }}</td>
                <td>{{ $row['ExpectedSumPVZ'] }}</td>
                <td>{{ $row['ExpectedSumCourier'] }}</td>
                <td>{{ $row['RetailAllQuantity'] }}</td>
                <td>{{ $row['RetailAllQuantityPVZ'] }}</td>
                <td>{{ $row['RetailAllQuantityCourier'] }}</td>
                <td>{{ $row['RetailFactQuantity'] }}</td>
                <td>{{ $row['RetailFactQuantityPVZ'] }}</td>
                <td>{{ $row['RetailFactQuantityCourier'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3>{{ __('States') }}</h3>

    <table class="table table-bordered table-sm">
        <thead class="thead-dark">
        <tr>
            <th>{{ __('Reason') }}</th>
            <th>{{ __('Quantity') }}</th>
        </tr>
        </thead>
        <tbody>
        @php
        $idCounterForReason = 0;
        @endphp
        @foreach ($statusData as $carrierName => $carrierStatusData)
            <tr>
               <td colspan="2" class="bg-info">{{ $carrierName }}</td>
            </tr>
            @foreach ($carrierStatusData as $statusName => $statusReasonData)
                <tr>
                    <td colspan="2" class="bg-white">{{ $statusName }}</td>
                </tr>
                @foreach ($statusReasonData as $reasonName => $reasonData)
                    @php
                        $idCounterForReason++;
                    @endphp
                    <tr>
                        <td>
                            {{ $reasonName }}
                            {!! Form::button(__('Orders'), ['class' => 'btn btn-sm btn-link', 'data-toggle' => 'collapse', 'data-target' => "#reason{$idCounterForReason}-orders", 'aria-expanded' => 'true', 'aria-controls' => "reason{$idCounterForReason}-orders"]) !!}

                            <div id="reason{{ $idCounterForReason }}-orders" class="collapse">
                                @foreach (($reasonData['orders'] ?? []) as $order)
                                    <span class="badge badge-info"><a href="{{ route('orders.edit', ['id' => \App\Order::getRealNumberById($order)]) }}" target="_blank" class="text-light">{{ $order }}</a></span>
                                @endforeach
                            </div>
                        </td>
                        <td>{{ $reasonData['quantity'] }}</td>
                    </tr>
                @endforeach
            @endforeach
        @endforeach
        </tbody>
    </table>
@endsection
