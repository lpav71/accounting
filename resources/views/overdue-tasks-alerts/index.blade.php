@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Overdue Tasks Managment') }}</h2>
            </div>
            <div class="pull-right">
                @can('overdueTask-create')
                    <a class="btn btn-sm btn-success" href="{{ route('overdue-tasks.create') }}"> {{ __('Create New Overdue Task Trashold') }}</a>
                @endcan
            </div>
        </div>
    </div>
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <table class="table table-light table-bordered table-responsive-sm">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Trashold') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
          @if (!empty($overdueTasks))
        @foreach ($overdueTasks as  $overdueTask)
            <tr>
                <td>{{ $overdueTask->id }}</td>
                <td>{{ $overdueTask->trashold }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('overdue-tasks.show',$overdueTask->id) }}">{{ __('Show') }}</a>
                            @can('overdueTask-edit')
                                <a class="dropdown-item" href="{{ route('overdue-tasks.edit',$overdueTask->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('overdueTask-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['overdue-tasks.destroy', $overdueTask->id],'style'=>'display:inline']) !!}
                                {!! Form::button(__('Delete'), ['class' => 'dropdown-item', 'type' => 'submit']) !!}
                                {!! Form::close() !!}
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        @endif
        </tbody>
    </table>
@endsection