@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Utm Groups Management') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-sm btn-success"
                   href="{{ route('utm-groups.create') }}"> {{ __('Create New Utm Group') }}</a>
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
    <table class="table table-light table-bordered table-responsive-sm table-sm">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Rule') }}</th>
            <th>{{ __('Sort order') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($utmGroups as $utmGroup)
            <tr>
                <td>
                    <a href="{{ route('utm-groups.edit', $utmGroup) }}">
                        {{ $utmGroup->id }}
                    </a>
                </td>
                <td>
                {{ $utmGroup->name }}
                <td>
                    {!! $utmGroup->rule !!}
                </td>
                <td>{{ $utmGroup->sort_order }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $utmGroups \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $utmGroups->render() !!}
@endsection