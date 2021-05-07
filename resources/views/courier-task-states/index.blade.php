@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Courier task states management') }}</h2>
            </div>
            <div class="pull-right">
                @can('orderState-create')
                    <a class="btn btn-sm btn-success" href="{{ route('courier-task-states.create') }}"> {{ __('Create new courier task state') }}</a>
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
        @foreach ($courierTaskStates as $courierTaskState)
            <tr>
                <td>
                    @can('courier-task')
                        <a href="{{ route('courier-task-states.edit', $courierTaskState) }}">
                            {{ $courierTaskState->id }}
                        </a>
                    @else
                        {{ $courierTaskState->id }}
                    @endcan
                </td>
                <td>@can('courier-task')
                        <a href="{{ route('courier-task-states.edit', $courierTaskState) }}">
                            {{ $courierTaskState->name }}
                        </a>
                    @else
                        {{ $courierTaskState->name }}
                    @endcan</td>
                <td>
                    @foreach($courierTaskState->previousStates as $previousState)
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
          * @var $courierTaskStates \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $courierTaskStates->render() !!}
@endsection