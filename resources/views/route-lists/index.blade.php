@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Route Lists') }}</h4>
            </div>
            <div class="pull-right">
                @can('order-create')
                    <a class="btn btn-sm btn-success" href="{{ route('route-lists.create') }}"> {{ __('Create New Route List') }}</a>
                @endcan
            </div>
        </div>
    </div>
    <table class="table table-light table--responsive table-sm table-bordered small-1 order-table hover-table">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Courier') }}</th>
            <th>{{ __('Route points') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($paginatedItems as $routeList)
            <tr style="background-color: #FFFFFF;">
                <td>
                    <a href="{{ route('route-lists.edit', $routeList) }}" class="text-dark d-block text-center" style="background-color: #FFFFFF;">{{ $routeList->id }}</a>
                </td>
                <td>{{ $routeList->courier->name }}</td>
                <td>{{ $routeList->routePoints()->count() }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {!! $paginatedItems->render() !!}
@endsection