@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Courier tasks') }}</h2>
            </div>
        </div>
    </div>
    @if ($message = Session::get('success'))
        <div class="alert alert-success mt-1">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning mt-1">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>{{ __('Whoops!') }}</strong> {{ __('There were some problems with your input.') }}<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="pull-right">
        <a class="btn btn-sm btn-success"
           href="{{ route('courier-tasks.create') }}"> {{ __('Courier tasks create') }}</a>
    </div>
    <table class="table table-light table-bordered">
        <thead class="bg-info">
        <tr>
            <th class="text-nowrap">{{ __('Date') }}</th>
            <th class="text-nowrap">{{ __('Id') }}</th>
            <th class="text-nowrap">{{ __('Address') }}</th>
            <th class="text-nowrap">{{ __('City') }}</th>
            <th class="text-nowrap">{{ __('Comment') }}</th>
            <th class="text-nowrap">{{ __('Done') }}</th>
            <th class="text-nowrap">{{ __('Time from:') }}</th>
            <th class="text-nowrap">{{ __('Time to:') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($courierTask as $task)
            <tr>
                <td><a href="{{ route('courier-tasks.edit', $task->id) }}"class="text-dark d-block text-center">{{ $task->date }}</a> </td>
                <td>{{ $task->id }}</td>
                <td>{{ $task->address }}</td>
                <td>{{ $task->city }}</td>
                <td>{{$task->comment}}</td>
                <td>
                    @if($task->currentState()->is_successful)
                        <i class="fa fa-check" aria-hidden="true"></i>
                    @else
                        <i class="fa fa-times" aria-hidden="true"></i>
                    @endif
                </td>
                <td>{{ $task->start_time }}</td>
                <td>{{$task->end_time}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $courierTask \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $courierTask->render() !!}
@endsection