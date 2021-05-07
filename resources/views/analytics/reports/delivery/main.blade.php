@extends('layouts.report')

@section('titleSection.title', __('Delivery report'))

@section('settingsSection.id', 'analytics')

@section('settingsSection.form')
    {!! Form::open(['route' => 'analytics.report.delivery.main','method'=>'GET', 'class' => 'form-group']) !!}
    <div class="row m-0">
        <div>
            <div class="input-group input-group-sm">
                <div class="input-group-prepend">
                    {!! Form::label('from', __('From'), ['class' => 'form-control form-control-sm m-0']) !!}
                </div>
                <div class="input-group-append">
                    {!! Form::text('from', $from ?? null, ['class' => 'form-control form-control-sm date rounded-0', 'autocomplete' => 'off']) !!}
                </div>
            </div>
        </div>
        <div class="ml-sm-3">
            <div class="input-group input-group-sm">
                <div class="input-group-prepend">
                    {!! Form::label('to', __('To'), ['class' => 'form-control form-control-sm m-0']) !!}
                </div>
                <div class="input-group-append">
                    {!! Form::text('to', $to ?? null, ['class' => 'form-control form-control-sm date rounded-0', 'autocomplete' => 'off']) !!}
                </div>
            </div>
        </div>
        <div class="ml-sm-3 mt-3 mt-sm-0">
            {!! Form::submit(__('Show report'), ['class' => 'btn btn-sm btn-dark', 'name' => 'submit']) !!}
{{--            {!! Form::button(--}}
{{--                __('Preferences'),--}}
{{--                [--}}
{{--                    'class' => 'btn btn-sm btn-link',--}}
{{--                    'data-toggle' => 'collapse',--}}
{{--                    'data-target' => '#preferences',--}}
{{--                    'aria-expanded' => 'true',--}}
{{--                    'aria-controls' => 'preferences',--}}
{{--                ]--}}
{{--            ) !!}--}}
        </div>
        @if(!empty($reportsData['reports'] ?? []))
            <div class="ml-md-3 mt-3 mt-md-0 ml-0 flex-fill text-right">
                {!! Form::select(
                    'sub_report_selected',
                    $reportsData['reports'] ?? [],
                    $sub_report_selected ?? null,
                    ['id'=>'subReportSelect', 'multiple' => false, 'class' => 'form-control form-control-sm d-inline w-auto']
                ) !!}
            </div>
        @endif
    </div>
{{--    <div id="preferences" class="card mt-2 p-0 rounded-0 collapse flex-row flex-wrap"--}}
{{--         aria-labelledby="preferencesHeader">--}}
{{--        <div class="col-12 p-3 border-top bg-light">--}}
{{--            {!! Form::submit(--}}
{{--                __('Save default'),--}}
{{--                ['class' => 'btn btn-sm btn-secondary pull-right col-sm-auto col-xs-12 mt-2 mt-sm-0 ml-0 ml-sm-3', 'name' => 'save']--}}
{{--            ) !!}--}}
{{--        </div>--}}
{{--    </div>--}}
    {!! Form::close() !!}
@endsection

@section('dataSection.content')
    <div class="row m-0">
        <div class="tab-content w-100">
            @if (empty($reportsData['reports'] ?? []))
                @if($submit ?? false)
                    {{ __('No data') }}
                @endif
            @else
                @foreach(($reportsData['data'] ?? []) as $reportId => $report)
                    <div class="w-100 tab-pane fade @if($reportId == $sub_report_selected || (is_null($sub_report_selected) && $loop->first)) show active @endif"
                         id="{{$reportId}}">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-dark">
                            <tr>
                                @foreach (($report['titles'] ?? []) as $title)
                                    <th>{{$title}}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach (($report['groups'] ?? []) as $reportGroupName => $reportGroup)
                                <tr class="bg-info">
                                    <td colspan="{{ count($report['titles'] ?? []) }}">{{ $reportGroupName }}</td>
                                </tr>
                                @foreach (($reportGroup['subgroups'] ?? []) as $reportSubGroupName => $reportSubGroup)
                                    <tr class="bg-secondary">
                                        <td colspan="{{ count($report['titles'] ?? []) }}">{{ $reportSubGroupName }}</td>
                                    </tr>
                                    @foreach (($reportSubGroup['rows'] ?? []) as $row)
                                        <tr>
                                            @foreach ($row as $cell)
                                                <td>{{ $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    <tr class="font-weight-bold">
                                        @foreach (($reportSubGroup['total'] ?? []) as $cell)
                                            <td>{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                @foreach (($reportGroup['rows'] ?? []) as $row)
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td>{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr class="font-weight-bold">
                                    @foreach (($reportGroup['total'] ?? []) as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr class="font-weight-bold bg-success">
                                @foreach (($report['total'] ?? []) as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @endforeach
            @endif

        </div>
    </div>
@endsection
