@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Route List States Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('orderState-create')
                    <a class="btn btn-sm btn-success" href="{{ route('route-list-states.create') }}"> {{ __('Create New Route List State') }}</a>
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
            <th>{{ __('Is editable Route List') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($routeListStates as $routeListState)
            <tr>
                <td>
                    @can('orderState-edit')
                        <a href="{{ route('route-list-states.edit', $routeListState) }}">
                            {{ $routeListState->id }}
                        </a>
                    @else
                        {{ $routeListState->id }}
                    @endcan
                </td>
                <td>@can('orderState-edit')
                        <a href="{{ route('route-list-states.edit', $routeListState) }}">
                            {{ $routeListState->name }}
                        </a>
                    @else
                        {{ $routeListState->name }}
                    @endcan</td>
                <td>
{{--                    @foreach($routeListState->previousStates as $previousState)--}}
{{--                        <label class="badge badge-success">{{ $previousState->name }}</label>--}}
{{--                    @endforeach--}}
                </td>
                <td class="text-center">@include('_common.icons.controls.isActive', ['isActive' => $routeListState->is_editable_route_list])</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $routeListStates \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $routeListStates->render() !!}
@endsection