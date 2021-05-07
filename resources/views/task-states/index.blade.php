@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Task States Management') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-sm btn-success" href="{{ route('task-states.create') }}"> {{ __('Create New Task State') }}</a>
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
    <table class="table table-light table-bordered table-responsive-sm">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Previous state') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($taskStates as $key => $taskState)
            <tr>
                <td><a href="{{ route('task-states.edit',$taskState->id) }}">{{ $taskState->id }}</a></td>
                <td>{{ $taskState->name }}</td>
                <td>
                    @foreach($taskState->previousStates as $previousState)
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
          * @var $taskStates \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $taskStates->render() !!}
@endsection