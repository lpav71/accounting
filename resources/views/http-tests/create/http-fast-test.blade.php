@extends('layouts.manage')

@section('titleSection.title', __('Create Http Fast Test'))

@section('settingsSection.id', 'tests')

@section('settingsSection.form')
    <div class="text-right pb-3 mb-3 border-bottom">
        <a class="btn btn-sm btn-primary" href="{{ route('http-tests.index') }}"> {{ __('Back') }}</a>
    </div>
@endsection

@section('dataSection.content')
    {!! Form::open(['route' => ['http-tests.store', \App\HttpTest::getHttpTestRouteByClass(\App\HttpFastTest::class)],'method'=>'POST']) !!}
    @include('http-tests.partials.form.http-fast-test-body')
    {!! Form::close() !!}
@endsection
