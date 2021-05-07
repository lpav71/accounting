@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Pay route points')}} {{$routeList->courier->name}}</h4>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('route-lists.edit', $routeList) }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
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
    {!! Form::model($routeList, ['route' => ['route-lists.update', $routeList],'method'=>'PATCH']) !!}
    {!! Form::hidden('updated_at_version', $routeList->updated_at->toDateTimeString()) !!}
    <div class="row pb-3 border-bottom">
        <div class="col-12 row">
            <div class="form-group col-xs-12 col-sm-2" id="deliveryDate">
                {!! Form::label('date_list', __('Date:')) !!}
                @php
                use Illuminate\Support\Carbon;
                @endphp
                {!! Form::text('date_list', Carbon::today()->format('d-m-Y'), ['class' => 'form-control date', 'autocomplete' => 'off']) !!}
            </div>
            <div class="form-group col-xs-12 col-sm-3">
                {!! Form::label('store_id', __('Store:')) !!}
                {!! Form::select('store_id', $stores, null, ['class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group col-xs-12 col-sm-3">
                {!! Form::label('accepted_funds', __('Accepted funds:')) !!}
                {!! Form::text('accepted_funds', null, ['class' => 'form-control']) !!}
            </div>
            <div class="form-group col-xs-12 col-sm-3">
                {!! Form::label('costs', __('Transport costs:')) !!}
                {!! Form::text('costs', null, ['class' => 'form-control']) !!}
            </div>
            <div class="form-group col-xs-12 col-sm-3">
                {!! Form::label('currency_id', __('Currency:')) !!}
                {!! Form::select('currency_id', $currencies, null, ['class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group col-xs-12 col-sm-3">
                {!! Form::label('cashbox_id', __('Cashbox:')) !!}
                {!! Form::select('cashbox_id', $cashboxes, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            {!! Form::submit(__('Save'), ['class' => 'btn btn-primary']) !!}
        </div>
    </div>
    @if($routeList->routePoints->isNotEmpty())
        <table class="table table-sm table-bordered small mt-3 hover-table table-responsive">
            <thead class="bg-info">
            <tr>
                <th class="text-nowrap">{{ __('Type') }}</th>
                <th class="text-nowrap">{{ __('P-t Id') }}</th>
                <th class="text-nowrap">{{ __('P-t State') }}</th>
                <th class="text-nowrap">{{ __('Time') }}</th>
                <th class="text-nowrap">{{ __('Address') }}</th>
                <th class="text-nowrap">{{ __('P-t Object Id') }}</th>
                <th class="text-nowrap">{{ __('Order number') }}</th>
                <th class="text-nowrap">{{ __('P-t Object State') }}</th>
                <th class="text-nowrap">{{ __('Cashbox if different') }}</th>
                <th class="text-nowrap">{{ __('Estimated delivery date:') }}</th>
            </tr>
            </thead>
            <tbody>
                @foreach($collection as $routePoint)

                    @include('route-lists._partials.route-point.row', ['routePoint' => $routePoint])

                @endforeach
            </tbody>
        </table>
    @endif
    {!! Form::close() !!}
@endsection