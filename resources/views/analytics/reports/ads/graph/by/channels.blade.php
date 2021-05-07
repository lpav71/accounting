@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Graph report') }}</h2>
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
        {!! Form::open(['route' => 'analytics.report.ads.graph.by.channels','method'=>'GET', 'class' => 'form-group']) !!}
        <div class="row m-0">
            <div>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        {!! Form::label('from', __('From'), ['class' => 'form-control form-control-sm m-0']) !!}
                    </div>
                    <div class="input-group-append">
                        {!! Form::text('from', $dateFrom, ['class' => 'form-control form-control-sm date rounded-0', 'autocomplete' => 'off']) !!}
                    </div>
                </div>
            </div>
            <div class="ml-sm-3">
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        {!! Form::label('to', __('To'), ['class' => 'form-control form-control-sm m-0']) !!}
                    </div>
                    <div class="input-group-append">
                        {!! Form::text('to', $dateTo, ['class' => 'form-control form-control-sm date rounded-0', 'autocomplete' => 'off']) !!}
                    </div>
                </div>
            </div>
            <div class="ml-sm-3 mt-3 mt-sm-0">
                {!! Form::submit(__('Show report'), ['class' => 'btn btn-sm btn-dark', 'name' => 'submit']) !!}
                {!! Form::button(__('Preferences'), ['class' => 'btn btn-sm btn-link', 'data-toggle' => 'collapse', 'data-target' => '#preferences', 'aria-expanded' => 'true', 'aria-controls' => 'preferences']) !!}
                <div class="d-inline-block border rounded p-1 bg-light pl-2">
                    {!! Form::label('n_average', __('N-Average'), ['class' => 'm-0']) !!}
                    {!! Form::input('text', 'n_average', $n_average, ['class' => 'form-control form-control-sm w-auto d-inline-block text-center', 'size' => 1, 'maxlength' => 1]) !!}
                </div>

            </div>
            <div class="ml-md-3 mt-3 mt-md-0 ml-0 flex-fill text-right">
                {!! Form::select('chart_selected', array_merge($charts, ['multiChart' => __('Multi Chart')]), $chart_selected, ['id'=>'chartSelect', 'multiple' => false, 'class' => 'form-control form-control-sm d-inline w-auto']) !!}
            </div>
        </div>
        <div id="preferences" class="card mt-2 p-0 rounded-0 collapse flex-row flex-wrap" aria-labelledby="preferencesHeader">
            <div class="col-sm-6 p-3">
                <div class="form-group">
                    {!! Form::label('successful_states[]', __('Summarize prices for items in statuses').':') !!}
                    {!! Form::select('successful_states[]', $orderDetailStates, $successful_states, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
                </div>
                <div class="form-group mb-0">
                    {!! Form::label('minimal_states[]', __('If not, then consider the minimum prices for positions with a guarantee in statuses').':') !!}
                    {!! Form::select('minimal_states[]', $orderDetailStates, $minimal_states, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
                </div>
            </div>
            <div class="col-sm-6 p-3">
                <div class="form-group">
                    {!! Form::label('utm_campaigns[]', __('UTM Campaigns').':') !!}
                    {!! Form::select('utm_campaigns[]', $utmCampaigns, $utm_campaigns, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker-searchable']) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('utm_sources[]', __('UTM Sources').':') !!}
                    {!! Form::select('utm_sources[]', $utmSources, $utm_sources, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker-searchable']) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('utm_groups[]', __('UTM Groups').':') !!}
                    {!! Form::select('utm_groups[]', $utmGroups, $utm_groups, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker-searchable']) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('devices[]', __('Devices').':') !!}
                    {!! Form::select('devices[]', $devicesGroups, $devices, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker-searchable']) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('ages[]', __('Ages').':') !!}
                    {!! Form::select('ages[]', $agesGroups, $ages, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker-searchable']) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('genders[]', __('Genders').':') !!}
                    {!! Form::select('genders[]', $gendersGroups, $genders, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker-searchable']) !!}
                </div>
            </div>
            <div class="col-12 p-3 border-top bg-light">{!! Form::submit(__('Save default'), ['class' => 'btn btn-sm btn-secondary pull-right col-sm-auto col-xs-12 mt-2 mt-sm-0 ml-0 ml-sm-3', 'name' => 'save']) !!}</div>
        </div>
        <div class="row m-0">
            <script>
                let Charts = {};
            </script>
            <div class="tab-content w-100">
            @foreach($graphs as $graphName => $graph)
                <div class="w-100 tab-pane fade @if($graphName == $chart_selected || (is_null($chart_selected) && $loop->first)) show active @endif" id="{{$graphName}}">
                    <canvas id="{{$graphName}}-chart" class="js-chart bg-white w-100"></canvas>
                    <script>
                        Charts['{{$graphName}}-chart'] = {!! $graph !!};
                    </script>
                </div>
            @endforeach
                <div class="w-100 tab-pane fade @if('multiChart' == $chart_selected) show active @endif" id="multiChart">
                    <div class="pt-2 pb-2">
                        {{ Form::select('multiChartFirst', array_merge([0 => __('No')], $charts), $multiChartFirst, ['id'=>'multiChartFirst', 'multiple' => false, 'class' => 'js-multiChart form-control form-control-sm d-inline w-auto']) }}
                        {{ Form::select('multiChartSecond', array_merge([0 => __('No')], $charts), $multiChartSecond, ['id'=>'multiChartSecond', 'multiple' => false, 'class' => 'js-multiChart form-control form-control-sm d-inline w-auto']) }}
                    </div>
                    <canvas id="multiChart-chart" class="js-chart bg-white w-100"></canvas>
                </div>
            </div>
        </div>
        {!! Form::hidden('report', 1) !!}
        {!! Form::close() !!}

    </div>
@endsection
