@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Ads report') }}</h2>
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
    <div class="col-12" id="analytics" style="z-index: 2000;">
        {!! Form::open(['route' => 'analytics.report.ads.by.channels.with.expenses','method'=>'GET', 'class' => 'form-group']) !!}
        <div class="row clearfix">
            <div>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        {!! Form::label('from', __('From'), ['class' => 'form-control form-control-sm m-0']) !!}
                    </div>
                    <div class="input-group-append">
                        {!! Form::text('from', $dateFrom, ['class' => 'form-control form-control-sm date', 'autocomplete' => 'off']) !!}
                    </div>
                </div>
            </div>
            <div>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        {!! Form::label('to', __('To'), ['class' => 'form-control form-control-sm m-0']) !!}
                    </div>
                    <div class="input-group-append">
                        {!! Form::text('to', $dateTo, ['class' => 'form-control form-control-sm date', 'autocomplete' => 'off']) !!}
                    </div>
                </div>
            </div>
            @can('analytics-products-list'){!! Form::submit(__('Save default'), ['class' => 'btn btn-sm pull-right col-sm-auto col-xs-12 mt-2 mt-sm-0 ml-0 ml-sm-5', 'name' => 'save']) !!}@endcan
        </div>
        <div class="row">
            <div class="form-group col-3">
                {!! Form::label('device', __('Device').':') !!}
                {!! Form::select('device', $devices, $device, ['multiple' => false, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
            <div class="form-group col-3">
                {!! Form::label('age', __('Age').':') !!}
                {!! Form::select('age', $ages, $age, ['multiple' => false, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
            <div class="form-group col-3">
                {!! Form::label('gender', __('Gender').':') !!}
                {!! Form::select('gender', $genders, $gender, ['multiple' => false, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-3">
                {!! Form::label('indicator_clicks_delta', __('Delta of clicks indicator').':') !!}
                {!! Form::text('indicator_clicks_delta', $indicator_clicks_delta, ['class' => 'form-control form-control-sm']) !!}
            </div>
            <div class="form-group col-3">
                {!! Form::label('indicator_min_conversions', __('Min conversions for price per order indicator').':') !!}
                {!! Form::text('indicator_min_conversions', $indicator_min_conversions, ['class' => 'form-control form-control-sm']) !!}
            </div>
            <div class="form-group col-3">
                {!! Form::label('show_utm', __('Show utm groups')) !!}
                {!! Form::checkbox('show_utm', 1, $utm, ['class' => 'form-control form-control-sm']) !!}
            </div>
            <div class="form-group col-3">
                {!! Form::label('count_for_today', __('Сount for at today rate')) !!}
                {!! Form::checkbox('count_for_today', 1, $todayCourse, ['class' => 'form-control form-control-sm']) !!}
            </div>
            <div class="form-group col-3">
                {!! Form::label('show_traffic', __('Show search engine traffic')) !!}
                {!! Form::checkbox('show_traffic', 1,$traffic, ['class' => 'form-control form-control-sm']) !!}
            </div>
            <div class="form-group col-3">
                {!! Form::label('compare_categories', __('Compare categories')) !!}
                {!! Form::checkbox('compare_categories', 1, $cmpCategories, ['class' => 'form-control form-control-sm']) !!}
            </div>
        </div>

        {!! Form::hidden('report', 1) !!}
        {!! Form::submit(__('Show report'), ['class' => 'btn btn-sm', 'name' => 'submit']) !!}
        {!! Form::close() !!}
    </div>
    <table class="table table-sm">
        <thead class="thead-light">
        <tr>
            <th class="sticky-top d-flex h6 pt-1 pb-1">
                <div>{{ __('Name') }}</div>{!! Form::input('text', 'filter', null, ['id'=> 'filterTable', 'class' => 'form-control form-input ml-1 h-25']) !!}
            </th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Clicks') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1" title="{{ __('Unique ClientID for Clicks') }}">{{__('Unique')}}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1" title="{{ __('Zero ClientID for Clicks') }}">{{__('Zero')}}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Visits') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Bounces') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Conversion') }}, %</th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Cost per click') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Costs') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Costs per order') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1" title="{{ __('Costs per successful order') }}">{{__('Successful costs')}}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Costs, %') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Orders') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1" title="{{__('Successful orders')}}">{{ __('Successful') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Price') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1 {{ $utm == 0 ? 'd-none' : ''}}">{{ __('UTM Groups') }}</th>
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Profit') }}</th>
            @can('expense-settings-view')
            <th class="align-top sticky-top h6 pt-1 pb-1">{{ __('Expenses') }}</th>
                @endcan
        </tr>
        </thead>
        @foreach ($data['groups'] as $groupName => $group)
            <tbody>
            <tr class="font-italic bg-secondary">
                <td colspan="18">
                    {{ $groupName }}
                </td>
            </tr>
            @if(isset($group['rows']))
                @foreach ($group['rows'] as $rowName => $row)
                    <tr class="js-row-data">
                        <td class="text-nowrap pt-1 pb-1">{{ $rowName }}</td>
                        <td class="pt-1 pb-1">{{ $row['clicks'] ?? 0 }}</td>
                        <td class="pt-1 pb-1" style="width: 5% !important;">{{ $row['unique_client_id'] ?? 0 }}</td>
                        <td class="pt-1 pb-1">{{ $row['zero_client_id'] ?? 0 }}</td>
                        <td class="pt-1 pb-1">{{ $row['visits'] ?? 0 }}</td>
                        <td class="pt-1 pb-1">{{ $row['bounce_visits'] ?? 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($row['visits']) && isset($row['orders']) && floatval($row['visits']) > 0 ? round(count($row['orders'])/(float)$row['visits'], 4) * 100 : 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($row['costs']) && isset($row['clicks']) && floatval($row['clicks']) > 0 ? round(((float)$row['costs'])/((float)$row['clicks']), 2) : 0 }}</td>
                        <td class="pt-1 pb-1">{{ $row['costs'] ?? 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($row['costs']) && isset($row['orders']) && count($row['orders']) > 0 ? round((float)$row['costs']/count($row['orders']), 2) : 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($row['costs']) && isset($row['successful_orders']) && count($row['successful_orders']) > 0 ? round((float)$row['costs']/count($row['successful_orders']), 2) : 0 }}</td>
                        <td class="pt-1 pb-1">{{ ($row['price'] ?? 0) ? round(($row['costs'] ?? 0) / $row['price'] * 100, 2) : 0}}</td>
                        <td @if(isset($row['orders']) && count($row['orders']))class="bg-info pt-1 pb-1" @else class="pt-1 pb-1" @endif>{{ isset($row['orders']) ? count($row['orders']) : 0 }}</td>
                        <td @if(isset($row['successful_orders']) && count($row['successful_orders']))class="bg-info pt-1 pb-1"@else class="pt-1 pb-1" @endif>{{ isset($row['successful_orders']) ? count($row['successful_orders']) : 0 }}</td>
                        <td class="pt-1 pb-1">{{ $row['price'] ?? 0 }}</td>
                        <td {{ $utm == 0 ? 'class=d-none' : 'class=pt-1 pb-1'}}>
                            @foreach ($row['utm_groups'] as $utmGroup)
                                <span class="badge badge-info text-nowrap d-block m-1">{{ $utmGroup->name }}</span>
                            @endforeach
                        </td>
                        <td class="pt-1 pb-1">
                            @if(isset($row['row_profit']))
                                <span class="badge badge-secondary">
                                {{ __('Profit') }}
                               <span class="analytics-profit"> {{ $row['row_profit']['profit'] }} </span>
                                    </span>
                        </td>
                        @can('expense-settings-view')
                        <td class="pt-1 pb-1">
                            <div class="d-flex">
                                    @foreach($row['row_profit']['expenses'] as $expense)
                                        <span class="badge badge-secondary expenses ml-1">
                                        <span class="expense-name">{{ $expense['name'] }}</span>
                                    <span class="analytics-expense">{{ $expense['summ'] }}</span>
                                    </span>
                                    @endforeach
                                @endif
                            </div>
                        </td>
                        @endcan
                        @can('expense-settings-view')
                        @if(!empty($row['orders']))
                            <td>
                                <a class="btn btn-success" href="{{ route('orders.expense.orders', ['orders' => array_keys($row['orders'])])}}">{{__('Orders')}}</a>
                            </td>
                            @endif
                        @endcan
                    </tr>
                @endforeach
            @endif
            @foreach ($group['totals'] as $totalName => $total)
                @if(count($total) > 0)
                    <tr class="font-weight-bold font-italic js-group-subtotal-data">
                        <td class="pt-1 pb-1">{{ $totalName }}</td>
                        <td class="pt-1 pb-1">{{ $total['clicks'] ?? 0 }}</td>
                        <td class="pt-1 pb-1" style="width: 5% !important;">{{ $total['unique_client_id'] ?? 0 }}</td>
                        <td class="pt-1 pb-1">{{ $total['zero_client_id'] ?? 0 }}</td>
                        <td class="pt-1 pb-1">{{ $total['visits'] ?? 0 }}</td>
                        <td class="pt-1 pb-1">{{ $total['bounce_visits'] ?? 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($total['visits']) && isset($total['orders']) && floatval($total['visits']) > 0 ? round(count($total['orders'])/(float)$total['visits'], 4) * 100 : 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($total['costs']) && isset($total['clicks']) && floatval($total['clicks']) > 0 ? round(((float)$total['costs'])/((float)$total['clicks']), 2) : 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($total['costs']) ? $total['costs'] : 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($total['costs']) && isset($total['orders']) && count($total['orders']) > 0 ? round((float)$total['costs']/count($total['orders']), 2) : 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($total['costs']) && isset($total['successful_orders']) && count($total['successful_orders']) > 0 ? round((float)$total['costs']/count($total['successful_orders']), 2) : 0 }}</td>
                        <td class="pt-1 pb-1">{{ ($total['price'] ?? 0) ? round(($total['costs'] ?? 0) / $total['price'] * 100, 2) : 0}}</td>
                        <td class="pt-1 pb-1">{{ isset($total['orders']) ? count($total['orders']) : 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($total['successful_orders']) ? count($total['successful_orders']) : 0 }}</td>
                        <td class="pt-1 pb-1">{{ isset($total['price']) ? $total['price'] : 0 }}</td>
                        <td {{ $utm == 0 ? 'class=d-none' : 'class=pt-1 pb-1'}}></td>
                        <td class="pt-1 pb-1">
                            @if((isset($total['profit'])))
                                <span class="badge badge-secondary  ml-1">
                                {{__('Profit')}}
                                    {{$total['profit']}}
                                </span>
                            @endif
                        </td>
                        @can('expense-settings-view')
                        <td class="pt-1 pb-1">
                            <div class="d-flex">
                                @if(isset($total['total_expenses']))
                                    <span class="badge badge-secondary"> {{ __('Expenses sum') . $total['total_expenses']}} </span>
                                @endif
                                @if(isset($total['expenses']))
                                    <span class="expenses d-flex">
                                    @foreach($total['expenses'] as $expense)
                                        <span class="badge badge-secondary ml-1">{{$expense['name']}} {{$expense['summ']}}</span>
                                    @endforeach
                                    </span>
                                @endif
                            </div>
                        </td>
                            @endcan
                    </tr>
                @endif
            @endforeach
            @foreach ($group['total'] as $totalName => $total)
                <tr class="font-weight-bold js-group-total-data">
                    <td class="pt-1 pb-1">{{ $totalName }}</td>
                    <td class="pt-1 pb-1">{{ $total['clicks'] ?? 0 }}</td>
                    <td class="pt-1 pb-1" style="width: 5% !important;">{{ $total['unique_client_id'] ?? 0 }}</td>
                    <td class="pt-1 pb-1">{{ $total['zero_client_id'] ?? 0 }}</td>
                    <td class="pt-1 pb-1">{{ $total['visits'] ?? 0 }}</td>
                    <td class="pt-1 pb-1">{{ $total['bounce_visits'] ?? 0 }}</td>
                    <td class="pt-1 pb-1">{{ isset($total['visits']) && isset($total['orders']) && floatval($total['visits']) > 0 ? round(count($total['orders'])/(float)$total['visits'], 4) * 100 : 0 }}</td>
                    <td class="pt-1 pb-1">{{ isset($total['costs']) && isset($total['clicks']) && floatval($total['clicks']) > 0 ? round(((float)$total['costs'])/((float)$total['clicks']), 2) : 0 }}</td>
                    <td class="pt-1 pb-1">{{ isset($total['costs']) ? $total['costs'] : 0 }}</td>
                    <td class="pt-1 pb-1">{{ isset($total['costs']) && isset($total['orders']) && count($total['orders']) > 0 ? round((float)$total['costs']/count($total['orders']), 2) : 0 }}</td>
                    <td class="pt-1 pb-1">{{ isset($total['costs']) && isset($total['successful_orders']) && count($total['successful_orders']) > 0 ? round((float)$total['costs']/count($total['successful_orders']), 2) : 0 }}</td>
                    <td class="pt-1 pb-1">{{ ($total['price'] ?? 0) ? round(($total['costs'] ?? 0) / $total['price'] * 100, 2) : 0}}</td>
                    <td class="pt-1 pb-1">{{ isset($total['orders']) ? count($total['orders']) : 0 }}</td>
                    <td class="pt-1 pb-1">{{ isset($total['successful_orders']) ? count($total['successful_orders']) : 0 }}</td>
                    <td class="pt-1 pb-1">{{ isset($total['price']) ? $total['price'] : 0 }}</td>
                    <td {{ $utm == 0 ? 'class=d-none' : 'class=pt-1 pb-1'}}></td>
                    <td class="pt-1 pb-1">
                        @if((isset($total['profit'])))
                            <span class="badge badge-secondary">
                            {{__('Profit')}}
                                {{$total['profit']}}
                            </span>
                        @endif
                    </td>
                    @can('expense-settings-view')
                    <td class="pt-1 pb-1">
                        <div class="d-flex">
                            @if(isset($total['total_expenses']))
                                <span class="badge badge-secondary"> {{ __('Expenses sum') . $total['total_expenses']}}</span>
                            @endif
                            @if(isset($total['expenses']))
                                    <span class="expenses d-flex">
                                @foreach($total['expenses'] as $expense)
                                    <span class="badge badge-secondary ml-1">{{$expense['name']}} {{$expense['summ']}}</span>
                                @endforeach
                                    </span>
                            @endif
                        </div>
                    </td>
                        @endcan
                </tr>
            @endforeach
            </tbody>
        @endforeach
        <tbody>
        @foreach ($data['total_rows'] as $totalName => $total)
            <tr class="font-weight-bold bg-info js-total-data">
                <td class="pt-1 pb-1">{{ $totalName }}</td>
                <td class="pt-1 pb-1">{{ $total['clicks'] ?? 0 }}</td>
                <td class="pt-1 pb-1" style="width: 5% !important;">{{ $total['unique_client_id'] ?? 0 }}</td>
                <td class="pt-1 pb-1">{{ $total['zero_client_id'] ?? 0 }}</td>
                <td class="pt-1 pb-1">{{ $total['visits'] ?? 0 }}</td>
                <td class="pt-1 pb-1">{{ $total['bounce_visits'] ?? 0 }}</td>
                <td class="pt-1 pb-1">{{ isset($total['visits']) && isset($total['orders']) && floatval($total['visits']) > 0 ? round(count($total['orders'])/(float)$total['visits'], 4) * 100 : 0 }}</td>
                <td class="pt-1 pb-1">{{ isset($total['costs']) && isset($total['clicks']) && floatval($total['clicks']) > 0 ? round(((float)$total['costs'])/((float)$total['clicks']), 2) : 0 }}</td>
                <td class="pt-1 pb-1">{{ isset($total['costs']) ? $total['costs'] : 0 }}</td>
                <td class="pt-1 pb-1">{{ isset($total['costs']) && isset($total['orders']) && count($total['orders']) > 0 ? round((float)$total['costs']/count($total['orders']), 2) : 0 }}</td>
                <td class="pt-1 pb-1">{{ isset($total['costs']) && isset($total['successful_orders']) && count($total['successful_orders']) > 0 ? round((float)$total['costs']/count($total['successful_orders']), 2) : 0 }}</td>
                <td class="pt-1 pb-1">{{ ($total['price'] ?? 0) ? round(($total['costs'] ?? 0) / $total['price'] * 100, 2) : 0}}</td>
                <td class="pt-1 pb-1">{{ isset($total['orders']) ? count($total['orders']) : 0 }}</td>
                <td class="pt-1 pb-1">{{ isset($total['successful_orders']) ? count($total['successful_orders']) : 0 }}</td>
                <td class="pt-1 pb-1">{{ isset($total['price']) ? $total['price'] : 0 }}</td>
                <td {{ $utm == 0 ? 'class=d-none' : 'class=pt-1 pb-1'}}></td>
                <td class="pt-1 pb-1">
                    @if(isset($total['profit']))
                        <span class="badge badge-secondary">
                        {{__('Profit')}}
                            {{$total['profit']}}
                        </span>
                    @endif
                </td>
                @can('expense-settings-view')
                <td class="pt-1 pb-1">
                    <div class="d-flex">
                        @if(isset($total['total_expenses']))
                            <span class="badge badge-secondary"> {{ __('Expenses sum') . $total['total_expenses']}} </span>
                        @endif
                        @if(isset($total['expenses']))
                                <span class="expenses d-flex">
                            @foreach($total['expenses'] as $expense)
                                <span class="badge badge-secondary ml-1">{{$expense['name']}} {{$expense['summ']}}</span>
                            @endforeach
                                </span>
                        @endif
                    </div>
                </td>
                    @endcan
            </tr>
        @endforeach
        </tbody>
        <tbody>
        @if(count($dataUtmGroups))
            <tr>
                <td class="bg-light" colspan="18">{{ __('UTM Groups') }}</td>
            </tr>
        @endif
        @foreach ($dataUtmGroups as $utmGroupName => $utmGroupRow)
            @php
                /**
                * @var \App\UtmGroup $utmGroup
                */
                $utmGroup = $utmGroupRow['utmGroup'];
            @endphp
            <tr>
                <td class="text-nowrap pt-1 pb-1">{{ $utmGroupName }}</td>
                <td class="text-nowrap text-center pt-1 pb-1">
                    @php
                        $indicator_clicks_from = !is_null($utmGroup->indicator_clicks) && !is_null($indicator_clicks_delta) ? floor($utmGroup->indicator_clicks * $reportDays * (100 - (int) $indicator_clicks_delta) / 100) : null;
                        $indicator_clicks_to = !is_null($utmGroup->indicator_clicks) && !is_null($indicator_clicks_delta) ? floor($utmGroup->indicator_clicks * $reportDays * (100 + (int) $indicator_clicks_delta) / 100) : null;
                    @endphp
                    <div
                            @if(!is_null($indicator_clicks_from) && $utmGroupRow['clicks'] <= $indicator_clicks_from)
                            class="bg-info"
                            @elseif (!is_null($indicator_clicks_to) && $utmGroupRow['clicks'] >= $indicator_clicks_to)
                            class="bg-warning"
                            @endif
                    >
                        {{ $utmGroupRow['clicks'] }}
                    </div>
                    <div class="border-top ml-0 mr-0 row small">
                        <div class="text-info w-50">{{ $indicator_clicks_from }}</div>
                        <div class="border-left text-warning w-50">{{ $indicator_clicks_to }}</div>
                    </div>
                </td>
                <td class="pt-1 pb-1" style="width: 5% !important;">{{ $utmGroupRow['unique_client_id'] ?? 0 }}</td>
                <td class="pt-1 pb-1">{{ $utmGroupRow['zero_client_id'] ?? 0 }}</td>
                <td class="pt-1 pb-1">{{ $utmGroupRow['visits'] }}</td>
                <td class="pt-1 pb-1">{{ $utmGroupRow['bounce_visits'] }}</td>
                <td class="pt-1 pb-1">{{ $utmGroupRow['visits'] > 0 ? round(count($utmGroupRow['orders'])/(float)$utmGroupRow['visits'], 4) * 100 : 0 }}</td>
                @php
                    $costPerClick = $utmGroupRow['clicks'] > 0 ? round(((float)$utmGroupRow['costs'])/((float)$utmGroupRow['clicks']), 2) : 0;
                @endphp
                <td class="text-nowrap text-center pt-1 pb-1">
                    <div
                            @if(!is_null($utmGroup->indicator_price_per_click_from) && $costPerClick <= $utmGroup->indicator_price_per_click_from)
                            class="bg-info"
                            @elseif (!is_null($utmGroup->indicator_price_per_click_to) && $costPerClick >= $utmGroup->indicator_price_per_click_to)
                            class="bg-warning"
                            @endif
                    >
                        {{ $costPerClick }}
                    </div>
                    <div class="border-top ml-0 mr-0 row small">
                        <div class="text-info w-50">{{ $utmGroup->indicator_price_per_click_from }}</div>
                        <div class="border-left text-warning w-50">{{ $utmGroup->indicator_price_per_click_to }}</div>
                    </div>
                </td>
                <td class="pt-1 pb-1">{{ $utmGroupRow['costs'] }}</td>
                <td class="pt-1 pb-1">{{ count($utmGroupRow['orders']) > 0 ? round((float)$utmGroupRow['costs']/count($utmGroupRow['orders']), 2) : 0 }}</td>
                @php
                    $costPerOrder = count($utmGroupRow['successful_orders']) > 0 ? round((float)$utmGroupRow['costs']/count($utmGroupRow['successful_orders']), 2) : 0;
                @endphp
                <td class="text-nowrap text-center pt-1 pb-1">
                    @if(count($utmGroupRow['successful_orders']) < (int) $indicator_min_conversions)
                        {{ $costPerOrder }}
                    @else
                        <div
                                @if(!is_null($utmGroup->indicator_price_per_order_from) && $costPerOrder <= $utmGroup->indicator_price_per_order_from)
                                class="bg-info"
                                @elseif (!is_null($utmGroup->indicator_price_per_order_to) && $costPerOrder >= $utmGroup->indicator_price_per_order_to)
                                class="bg-warning"
                                @endif
                        >
                            {{ $costPerOrder }}
                        </div>
                        <div class="border-top ml-0 mr-0 row small">
                            <div class="text-info w-50">{{ $utmGroup->indicator_price_per_order_from }}</div>
                            <div class="border-left text-warning w-50">{{ $utmGroup->indicator_price_per_order_to }}</div>
                        </div>
                    @endif
                </td>
                <td class="pt-1 text-center pb-1">
                    @php
                        $costs = ($utmGroupRow['price'] ?? 0) ? round(($utmGroupRow['costs'] ?? 0) / $utmGroupRow['price'] * 100, 2) : 0;
                        $utmGroup = \App\UtmGroup::where(['name' => $utmGroupName])->first();
                    @endphp
                    @if($utmGroup)
                        @if($utmGroup->minimum_costs && $costs < $utmGroup->minimum_costs)
                            <div class="bg-info">
                                {{ $costs }}
                            </div>
                        @elseif($utmGroup->maximum_costs && $costs > $utmGroup->maximum_costs)
                            <div class="bg-warning">
                                {{ $costs }}
                            </div>
                        @else
                            {{ $costs }}
                        @endif
                    @else
                        {{ $costs }}
                    @endif
                </td>
                <td class="pt-1 pb-1">{{ count($utmGroupRow['orders']) }}</td>
                <td class="pt-1 pb-1">{{ count($utmGroupRow['successful_orders']) }}</td>
                <td class="pt-1 pb-1">{{ $utmGroupRow['price'] }}</td>
                <td {{ $utm == 0 ? 'class=d-none' : 'class=pt-1 pb-1'}}></td>
                <td class="pt-1 pb-1">
                    @if(isset($utmGroupRow['profit']))
                        <span class="badge badge-secondary">
                        {{__('Profit')}}
                            {{$utmGroupRow['profit']}}
                         </span>
                    @endif
                </td>
                @can('expense-settings-view')
                <td class="pt-1 pb-1">
                    <div class="d-flex">
                        @if(isset($utmGroupRow['total_expenses']))
                            <span class="badge badge-secondary">{{ __('Expenses sum') . $utmGroupRow['total_expenses']}}</span>
                        @endif
                            @if(isset($utmGroupRow['expenses']) && count($utmGroupRow['expenses']))
                                    <span class="expenses d-flex">
                                @foreach($utmGroupRow['expenses'] as $expense)
                                    <span class="badge badge-secondary ml-1">{{$expense['name']}} {{$expense['summ']}}</span>
                                    @endforeach
                                    </span>
                                @endif
                    </div>
                </td>
                    @endcan
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
