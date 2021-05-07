@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Task Priorities Management') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-sm btn-success" href="{{ route('task-priorities.create') }}"> {{ __('Create New Task Priority') }}</a>
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
            <th>@sortablelink('id', __('Id'))</th>
            <th>{{ __('Name') }}</th>
            <th>@sortablelink('rate', __('Rate'))</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($taskPriorities as $key => $taskPriority)
            <tr>
                <td><a href="{{ route('task-priorities.edit',$taskPriority->id) }}">{{ $taskPriority->id }}</a></td>
                <td>{{ $taskPriority->name }}</td>
                <td>{{ $taskPriority->rate }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $taskPriorities \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $taskPriorities->render() !!}
@endsection