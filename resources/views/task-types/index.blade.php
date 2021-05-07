@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Task Types Management') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-sm btn-success"
                   href="{{ route('task-types.create') }}"> {{ __('Create New Task Type') }}</a>
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
        </tr>
        </thead>
        <tbody>
        @foreach ($taskTypes as $key => $taskType)
            <tr style="background-color: {{$taskType->color}};">
                <td><a href="{{ route('task-types.edit',$taskType->id) }}">{{ $taskType->id }}</a></td>
                <td>{{ $taskType->name }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $taskTypes \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $taskTypes->render() !!}
@endsection
