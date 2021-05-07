@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Edit Task') }}</h4>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('tasks.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
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
    {!! Form::model($task, ['method' => 'PATCH','route' => ['tasks.update', $task->id]]) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Theme:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Theme'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('task_state_id', __('Task state:')) !!}
                {!! Form::select('task_state_id', $taskStates, $task->currentState()['id'], ['class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('description', __('Description:')) !!}
                {!! Form::textarea('description', null, ['placeholder' => __('Description'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('task_priority_id', __('Priority:')) !!}
                {!! Form::select('task_priority_id', $priorities, null, ['class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('task_type_id', __('Type:')) !!}
                {!! Form::select('task_type_id', $types, null, ['class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('order_id', __('Order:')) !!}
                {!! Form::select('order_id', $orders, null, ['class' => 'form-control selectpicker-searchable']) !!}
                {{--{!! Form::select('order_id', [], null, ['data-async-select-url'=>route('orders.async.selector'), 'class' => 'form-control async-select', 'data-async-select-loaded' => '0', 'data-async-select-id' => $task->order_id]) !!}--}}
            </div>
            <div class="form-group">
                {!! Form::label('customer_id', __('Customer:')) !!}
                {!! Form::select('customer_id', $customers, null, ['class' => 'form-control selectpicker-searchable']) !!}
                {{--{!! Form::select('customer_id', [], null, ['data-async-select-url'=>route('customers.async.selector'), 'class' => 'form-control async-select', 'data-async-select-loaded' => '0', 'data-async-select-id' => $task->customer_id]) !!}--}}
            </div>
            <div class="form-group">
                {!! Form::label('performer_user_id', __('Performer:')) !!}
                {!! Form::select('performer_user_id', $users, null, ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('deadline_date', __('Deadline date:')) !!}
                        {!! Form::text('deadline_date', null, ['class' => 'form-control date-picker', 'required'])!!}
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        {!! Form::label('deadline_time', __('Deadline time:')) !!}
                        {!! Form::text('deadline_time', null, ['class' => 'form-control time-picker', 'required']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary btn-sm">{{ __('Save Task') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
    @if($taskComments->count())
        <table class="table table-light table-bordered table-sm mt-5">
            <thead class="thead-light">
            <tr>
                <th>{{ __('Time') }}</th>
                <th>{{ __('Author') }}</th>
                <th>{{ __('Comment') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($taskComments as $comment)
                <tr>
                    <td style="width: 50px;" class="text-nowrap">{{ $comment->created_at->format('d-m-Y H:i') }}</td>
                    <td style="width: 50px;" class="text-nowrap">{{ $comment->author->name }}</td>
                    <td>{{ $comment->comment }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    {!! Form::open(['route' => ['tasks.comment.add', $task],'method'=>'POST']) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::textarea('comment', null, ['placeholder' => __('Comment'),'class' => 'form-control mt-5', 'required']) !!}
                {!! Form::hidden('task_id', $task->id) !!}
            </div>
        </div>
        <div class="col-12 text-right">
            <button type="submit" class="btn btn-info btn-sm">{{ __('Add comment') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection
