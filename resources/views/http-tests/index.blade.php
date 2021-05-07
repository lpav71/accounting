@extends('layouts.manage')

@section('titleSection.title', __('Tests Management'))

@section('settingsSection.id', 'tests')

@section('settingsSection.form')
    <div class="col-12 text-right p-0 mb-3">
        <a class="btn btn-sm btn-success" href="{{ route('http-tests.create', ['type' => \App\HttpTest::getHttpTestRouteByClass(\App\HttpFastTest::class)]) }}"> {{ __('Create New Fast Http Test') }}</a>
    </div>
@endsection

@section('dataSection.content')
    <table class="table table--responsive table-sm table-bordered small order-table">
        <thead class="thead-light">
        <tr>
            <th class="align-middle text-nowrap">{{ __('Id') }}</th>
            <th class="align-middle">{{ __('Name') }}</th>
            <th class="align-middle">{{ __('URL') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($tests as $test)
            <tr>
                <td><a href="{{ route('http-tests.edit',$test) }}">{{ $test->id }}</a></td>
                <td>{{ $test->name ?? '' }}</td>
                <td>{{ $test->url ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $tests \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $tests->render() !!}
@endsection
