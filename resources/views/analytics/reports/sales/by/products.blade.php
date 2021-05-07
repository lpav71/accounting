@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Sales report by products') }}</h2>
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
        {!! Form::open(['route' => 'analytics.report.sales.by.products','method'=>'GET', 'class' => 'form-group']) !!}
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
            @can('analytics-products-list'){!! Form::submit(__('Save default'), ['class' => 'btn btn-sm pull-right col-sm-auto col-xs-12 mt-2 mt-sm-0 ml-0 ml-sm-5', 'name' => 'save']) !!}@endcan
        </div>
        <div class="form-group mt-4">
            {!! Form::label('parts_brand_list[]', __('Show parts of product').':') !!}
            {!! Form::select('parts_brand_list[]', \App\Manufacturer::all()->pluck('name', 'id')->prepend(__('No'), 0), $parts_brand_list, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        <div class="form-group mt-4">
            {!! Form::label('successful_state', __('The status of the delivered products').':') !!}
            {!! Form::select('successful_state', array_merge([0 => __('No')], $orderDetailStates->toArray()), $successful_state, ['class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('progress_states[]', __('The status of products in process').':') !!}
            {!! Form::select('progress_states[]', $orderDetailStates, $progress_states, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        <div class="form-group mt-4">
            {!! Form::label('return_state', __('The status of the returned product').':') !!}
            {!! Form::select('return_state', array_merge([0 => __('No')], $orderDetailStates->toArray()), $return_state, ['class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('carriers[]', __('Carriers').':') !!}
            {!! Form::select('carriers[]', \App\Carrier::all()->pluck('name', 'id'), $carriers, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('channel', __('Channel').':') !!}
            {!! Form::select('channel', array_merge([0 => ''], \App\Channel::where('is_hidden',0)->pluck('name', 'id')->toArray()), $channel, ['multiple' => false, 'class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        {!! Form::hidden('report', 1) !!}
        {!! Form::submit(__('Show report'), ['class' => 'btn btn-sm', 'name' => 'submit']) !!}
        {!! Form::close() !!}
    </div>
    <table class="table table-light table-bordered table-sm">
        <thead class="thead-light">
        <tr>
            <th>
                <div>{{ __('Name') }}</div>{!! Form::input('text', 'filter', null, ['id'=> 'filterTable', 'class' => 'form-control form-input']) !!}
            </th>
            <th class="align-top">{{ __('Reference') }}</th>
            <th class="align-top">{{ __('Delivered') }}</th>
            <th class="align-top">{{ __('In the process separately') }}</th>
            <th class="align-top">{{ __('In the process composition') }}</th>
            <th class="align-top">{{ __('Returned') }}</th>
        </tr>
        </thead>
        @if(isset($data['groups']))
            @foreach ($data['groups'] as $groupName => $group)
                <tbody>
                <tr class="font-italic bg-secondary">
                    <td colspan="6">
                        {{ $groupName }}
                    </td>
                </tr>
                @foreach ($group['rows'] as $rowName => $row)
                    <tr class="js-row-data">
                        <td>{{ $rowName }}</td>
                        <td>{{ $row['reference'] }}</td>
                        <td>{{ isset($row['delivered']) ? $row['delivered'] : 0 }}</td>
                        <td>{{ isset($row['process_separately']) ? $row['process_separately'] : 0 }}</td>
                        <td>{{ isset($row['process_composition']) ? $row['process_composition'] : 0 }}</td>
                        <td>{{ isset($row['returned']) ? $row['returned'] : 0 }}</td>
                    </tr>
                @endforeach
                @foreach ($group['total'] as $rowName => $row)
                    <tr class="font-weight-bold js-group-total-data-sum">
                        <td>{{ $rowName }}</td>
                        <td></td>
                        <td>{{ isset($row['delivered']) ? $row['delivered'] : 0 }}</td>
                        <td>{{ isset($row['process_separately']) ? $row['process_separately'] : 0 }}</td>
                        <td>{{ isset($row['process_composition']) ? $row['process_composition'] : 0 }}</td>
                        <td>{{ isset($row['returned']) ? $row['returned'] : 0 }}</td>
                    </tr>
                @endforeach
                @if(isset($group['parts_rows']) && !empty($group['parts_rows']) && in_array($groupName, \App\Manufacturer::whereIn('id', $parts_brand_list)->pluck('name')->toArray()))
                    <tr class="font-weight-bold js-group-total-data-sum">
                        <td>{{ __('Component parts of products') }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @foreach($group['parts_rows'] as $rowName => $row)
                        <tr>
                            <td>{{ $rowName }}</td>
                            <td>{{ $row['reference'] }}</td>
                            <td>{{ isset($row['delivered']) ? $row['delivered'] : 0 }}</td>
                            <td>{{ isset($row['process_separately']) ? $row['process_separately'] : 0 }}</td>
                            <td>{{ isset($row['process_composition']) ? $row['process_composition'] : 0 }}</td>
                            <td>{{ isset($row['returned']) ? $row['returned'] : 0 }}</td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            @endforeach
        @endif
        @if(isset($data['total_rows']))
            @foreach ($data['total_rows'] as $rowName => $row)
                <tr class="font-weight-bold bg-info js-total-data-sum">
                    <td>{{ $rowName }}</td>
                    <td></td>
                    <td>{{ isset($row['delivered']) ? $row['delivered'] : 0 }}</td>
                    <td>{{ isset($row['process_separately']) ? $row['process_separately'] : 0 }}</td>
                    <td>{{ isset($row['process_composition']) ? $row['process_composition'] : 0 }}</td>
                    <td>{{ isset($row['returned']) ? $row['returned'] : 0 }}</td>
                </tr>
            @endforeach
        @endif
    </table>
@endsection