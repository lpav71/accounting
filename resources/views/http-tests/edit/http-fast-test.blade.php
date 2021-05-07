@extends('layouts.manage')

@section('titleSection.title', __('Edit Http Fast Test')." ".$data['id'])

@section('settingsSection.id', 'tests')

@section('settingsSection.form')
    <div class="text-right pb-3 mb-3 border-bottom">
        <a class="btn btn-sm btn-primary" href="{{ route('http-tests.index') }}"> {{ __('Back') }}</a>
    </div>
@endsection

@section('dataSection.content')
    {!! Form::open(['route' => ['http-tests.update', $data['id']],'method'=>'POST']) !!}
    @include('http-tests.partials.form.http-fast-test-body', ['data' => $data])
    {!! Form::close() !!}
@endsection
