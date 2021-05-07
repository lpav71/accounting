@extends('layouts.manage')

@section('titleSection.title', __('Test\'s Incidents'))

@section('settingsSection.id', 'testIncidents')

@section('settingsSection.form')
@endsection

@section('dataSection.content')
    <table class="table table--responsive table-sm table-bordered small order-table">
        <thead class="thead-light">
        <tr>
            <th class="align-middle text-nowrap">{{ __('Id') }}</th>
            <th class="align-middle">{{ __('Time') }}</th>
            <th class="align-middle">{{ __('Test name') }}</th>
            <th class="align-middle">{{ __('URL') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($incidents as $incident)
            <tr>
                <td><a href="{{ route('http-test-incidents.view',$incident) }}">{{ $incident->id }}</a></td>
                <td>{{ $incident->created_at ?? '' }}</td>
                <td>{{ $incident->test->name ?? '' }}</td>
                <td>{{ $incident->test->url ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $incidents \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $incidents->render() !!}
@endsection
