@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Tasks Management') }}</h4>
            </div>
            <div class="pull-right">
                <a class="btn btn-sm btn-success" href="{{ route('tasks.create') }}"> {{ __('Create New Task') }}</a>
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
    {{ Form::open(['route' => 'tasks.index','method'=>'GET']) }}
    <table class="table table--responsive table-sm table-bordered small order-table">
        <thead class="thead-light">
        <tr>
            <th class="align-middle text-nowrap">@sortablelink('id', __('Id'))</th>
            <th class="align-middle">@sortablelink('created_at', __('Date'))</th>
            <th class="align-middle">{{ __('Theme') }}</th>
            <th class="align-middle">{{ __('Channel') }}</th>
            <th class="align-middle">{{ __('Description') }}</th>
            <th class="align-middle">{{ __('Customer') }}</th>
            <th class="align-middle">{{ __('Order') }}</th>
            <th class="align-middle">{{ __('Performer') }}</th>
            <th class="align-middle">{{ __('Priority') }}</th>
            <th class="align-middle">{{ __('Type') }}</th>
            <th class="align-middle">{{ __('State') }}</th>
            <th class="align-middle">{{ __('Deadline') }}</th>
            <th class="align-middle border-right-0"> {{ __('Author') }}</th>
            <th class="align-middle border-left-0 text-nowrap">
                {{ Form::button('<i class="fa fa-search"></i>', ['class' => 'btn btn-sm', 'type' => 'submit']) }}
                <a class="btn btn-sm" href="{{ route('tasks.index') }}"><i class="fa fa-close"></i></a>
            </th>
        </tr>
        </thead>
        <tbody>
        <tr id="searchOrders">
            <td class="p-0">{{ Form::text('id', Request::input('id'), ['class' => 'form-control form-control-sm search-input text-center', 'size' => 2]) }}</td>
            <td class="p-0">{!! Form::text('date', Request::input('date'), ['class' => 'form-control form-control-sm date search-input text-center', 'size' => 3]) !!}</td>
            <td></td>
            <td class="p-0">{{Form::select('channel', $channels, Request::input('channel'), ['class' => 'form-control form-control-sm selectpicker'])}}</td>
            <td></td>
            <td class="p-0" area-label="{{ __('Customer') }}"><select data-async-select-url='{{route('customers.async.selector')}}', class="form-control async-select form-control-sm" , data-async-select-loaded="0" , data-async-select-id="{{Request::input('customer')}}" name="customer"></select></td>
            <td class="p-0" area-label="{{ __('Customer') }}"><select data-async-select-url='{{route('orders.async.selector')}}', class="form-control async-select form-control-sm" , data-async-select-loaded="0" , data-async-select-id="{{Request::input('orderid')}}" name="orderid"></select></td>
            {{--<td class="p-0">{{ Form::select('orderid', \App\Order::all()->keyBy('id')->map('App\Order::getDisplayNumberFromModel')->prepend('--', 0) ,Request::input('orderid'), ['class' => 'form-control form-control-sm selectpicker-searchable']) }}</td>--}}
            <td class="p-0">{{ Form::select('performer', \App\User::all()->keyBy('id')->pluck('name', 'id')->prepend('--', 0) ,Request::input('performer'), ['class' => 'form-control form-control-sm selectpicker-searchable']) }}</td>
            <td class="p-0">{{ Form::select('priority', \App\TaskPriority::orderByDesc('rate')->pluck('name', 'id')->prepend('--', 0) ,Request::input('priority'), ['class' => 'form-control form-control-sm selectpicker']) }}</td>
            <td class="p-0">{{ Form::select('type', \App\TaskType::orderByDesc('name')->pluck('name', 'id')->prepend('--', 0) ,Request::input('type'), ['class' => 'form-control form-control-sm selectpicker']) }}</td>
            <td class="p-0">{{ Form::select('state', \App\TaskState::pluck('name', 'id')->prepend('--', 0) ,Request::input('state'), ['class' => 'form-control form-control-sm selectpicker']) }}</td>
            <td class="p-0">{!! Form::text('deadlinedate', Request::input('deadlinedate'), ['class' => 'form-control form-control-sm date search-input text-center', 'size' => 3]) !!}</td>
            <td class="p-0"
                colspan="2">{{ Form::select('author', \App\User::all()->keyBy('id')->pluck('name', 'id')->prepend('--', 0) ,Request::input('author')??0, ['class' => 'form-control form-control-sm selectpicker-searchable']) }}</td>
        </tr>
        @foreach ($tasks as $key => $task)
            <tr style="background-color: {{ $task->currentState()['color'] }};">
                <td><a href="{{ route('tasks.edit',$task->id) }}">{{ $task->id }}</a></td>
                <td class="text-nowrap">
                    <div>{{ $task->created_at->format('d-m-Y') }}</div>
                    <div>{{ $task->created_at->format('H:i') }}</div>
                </td>
                <td>{{ $task->name }}</td>
                <td>{{ isset($task->order->channel->name) ? $task->order->channel->name : __('No') }}</td>
                <td>{{ $task->description }}</td>
                <td class="text-nowrap">{{ $task->customer->full_name }}</td>
                <td><a href="{{ route('orders.edit', ['order_id' => $task->order->id]) }}"
                       target="_blank">{{ $task->order->getDisplayNumber() }}</a></td>
                <td>{{ $task->performer->name }}</td>
                <td class="text-nowrap">{{ $task->priority->name }}</td>
                <td class="text-nowrap">{{ $task->type->name }}</td>
                <td class="text-nowrap">{{ $task->currentState() ? $task->currentState()->name : ''}}</td>
                <td class="text-nowrap">
                    <div>{{ $task->deadline_date }}</div>
                    <div>{{ $task->deadline_time }}</div>
                </td>
                <td colspan="2">{{ is_null($task->author) ? __('System') : $task->author->name }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{Form::close()}}
    @php
        {{
        /**
          * @var $tasks \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $tasks->render() !!}
@endsection
