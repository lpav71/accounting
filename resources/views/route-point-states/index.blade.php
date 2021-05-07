@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Route Point States Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('orderState-create')
                    <a class="btn btn-sm btn-success" href="{{ route('route-point-states.create') }}"> {{ __('Create New Route Point State') }}</a>
                @endcan
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
            <th>{{ __('Previous state') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($routePointStates as $routePointState)
            <tr>
                <td>
                    @can('orderState-edit')
                        <a href="{{ route('route-point-states.edit', $routePointState) }}">
                            {{ $routePointState->id }}
                        </a>
                    @else
                        {{ $routePointState->id }}
                    @endcan
                </td>
                <td>@can('orderState-edit')
                        <a href="{{ route('route-point-states.edit', $routePointState) }}">
                            {{ $routePointState->name }}
                        </a>
                    @else
                        {{ $routePointState->name }}
                    @endcan</td>
                <td>
                    @foreach($routePointState->previousStates as $previousState)
                        <label class="badge badge-success">{{ $previousState->name }}</label>
                    @endforeach
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $routePointStates \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $routePointStates->render() !!}
@endsection