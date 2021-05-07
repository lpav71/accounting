@extends('layouts.manage')

@section('titleSection.title', __('Http test incident')." ".$incident->id)

@section('settingsSection.id', 'testIncident')

@section('settingsSection.form')
    <div class="text-right pb-3 mb-3 border-bottom">
        <a class="btn btn-sm btn-primary" href="{{ route('http-test-incidents.index') }}"> {{ __('Back') }}</a>
    </div>
@endsection

@section('dataSection.content')
<div class="card">
    <div class="card-header">
        {{ $incident->created_at->format('d-m-Y H:i:s') }} {{ Html::link(route('http-tests.edit', $incident->test), $incident->test->name) }}
    </div>
    <div class="card-body">
        <ul>
            <li>{{ $incident->test->url }}</li>
            <li>{{ $incident->tick->message }}</li>
        </ul>
    </div>
</div>
@endsection
