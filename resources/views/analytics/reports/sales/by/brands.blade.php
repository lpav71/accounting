@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Sales report') }}</h2>
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
    <div class="col-12" id="analytics">
        {!! Form::open(['route' => 'analytics.report.sales.by.brands','method'=>'GET', 'class' => 'form-group']) !!}
        <div class="row clearfix">
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
            <div>
                <div class="form-group form-check m-0 ml-3">
                    {!! Form::checkbox('is_delivery_period', 1, $isDeliveryPeriod, ['class' => 'form-check-input']) !!}
                    {!! Form::label('is_delivery_period', __('By delivery date'), ['class' => 'form-check-label']) !!}
                </div>
            </div>
            @if(auth()->user()->hasAnyPermission('analytics-products-list','analytics-products-list-without-wholesale')){!! Form::submit(__('Save default'), ['class' => 'btn btn-sm pull-right col-sm-auto col-xs-12 mt-2 mt-sm-0 ml-0 ml-sm-5', 'name' => 'save']) !!}@endif
        </div>
        <div class="form-group mt-4">
            {!! Form::label('successful_states[]', __('Summarize prices for items in status').':') !!}
            {!! Form::select('successful_states[]', $orderDetailStates, $successful_states, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('minimal_states[]', __('If not, then consider the minimum prices for positions with a guarantee in statuses').':') !!}
            {!! Form::select('minimal_states[]', $orderDetailStates, $minimal_states, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('carriers[]', __('Carriers').':') !!}
            {!! Form::select('carriers[]', \App\Carrier::all()->pluck('name', 'id'), $carriers, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        <div class="form-group col-3">
            {!! Form::label('count_for_today', __('Сount for at today rate')) !!}
            {!! Form::checkbox('count_for_today', 1, $todayCourse, ['class' => 'form-control form-control-sm']) !!}
        </div>
        {!! Form::hidden('report', 1) !!}
        {!! Form::submit(__('Show report'), ['class' => 'btn btn-sm', 'name' => 'submit']) !!}
        {!! Form::close() !!}
    </div>
    <table class="table table-light table-bordered table-sm">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Orders') }}</th>
            <th>{{ __('Price') }}</th>
            @can('analytics-products-list')<th>{{ __('Wholesale Price') }}</th>@endcan
        </tr>
        </thead>
        <tbody>
        @foreach ($data['groups'] as $groupName => $group)
            <tr class="font-italic bg-secondary">
                <td colspan="4">
                    {{ $groupName }}
                </td>
            </tr>
            @foreach ($group['rows'] as $rowName => $row)
                <tr>
                    <td>{{ $rowName }}</td>
                    <td>{{ count($row['orders']) }}</td>
                    <td>{{ $row['price'] }}</td>
                    @can('analytics-products-list')<td>{{ $row['wholesale_price'] }}</td>@endcan
                </tr>
            @endforeach
            @foreach ($group['total'] as $totalName => $total)
                <tr class="font-weight-bold">
                    <td>{{ $totalName }}</td>
                    <td>{{ count($total['orders']) }}</td>
                    <td>{{ $total['price'] }}</td>
                    @can('analytics-products-list')<td>{{ $total['wholesale_price'] }}</td>@endcan
                </tr>
            @endforeach
        @endforeach
        @foreach ($data['total_rows'] as $totalName => $total)
            <tr class="font-weight-bold bg-info">
                <td>{{ $totalName }}</td>
                <td>{{ count($total['orders']) }}</td>
                <td>{{ $total['price'] }}</td>
                @can('analytics-products-list')<td>{{ $total['wholesale_price'] }}</td>@endcan
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection