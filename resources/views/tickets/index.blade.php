@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Tickets') }}</h2>
            </div>
            <div class="pull-right">
                @can('ticket-create')
                    <a class="btn btn-sm btn-success"
                       href="{{ route('tickets.create') }}"> {{ __('Create new ticket') }}</a>
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
    {{ Form::open(['method'=>'GET']) }}
    <table class="table table-light table-bordered table-responsive-xs">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Ticket state') }}</th>
            <th>{{ __('Ticket role') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
            <tr id="searchOrders">
                <td></td>
                <td></td>
                <td class="p-0" area-label="{{ __('Channel') }}">{{ Form::select('state', \App\TicketState::all()->pluck('name', 'id')->prepend('--', 0), Request::input('state'), ['class' => 'form-control form-control-sm selectpicker']) }}</td>
                <td></td>
                <td class="text-center p-1">
                    {{ Form::button('<i class="fa fa-search"></i>', ['class' => 'btn btn-sm', 'type' => 'submit']) }}
                    <a class="btn btn-sm" href="{{ route('tickets.index') }}"><i class="fa fa-close"></i></a>
                </td>
            </tr>
        @foreach ($tickets as $key => $ticket)
            <tr>
                <td>{{ $ticket->id }}</td>
                <td>{{ $ticket->name }}</td>
                <td>{{ $ticket->currentState()->name }}</td>
                @if($ticket->performer_user_id == Auth::id())
                    <td>{{ __('Ticket performer')}}</td>
                @elseif($ticket->creator_user_id == Auth::id())
                    <td>{{ __('Creator') }}</td>
                @elseif($ticket->users->contains(Auth::user()))
                    <td>{{ __('Member') }}</td>
                @else
                    <td>{{ __('Admin') }}</td>
                @endif
                <td class="text-right">
                    @can('ticket-list')
                        <a href="{{ route('tickets.show',$ticket->id) }}">{{ __('Show') }}</a>
                    @endcan
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{Form::close()}}
    @php
        {{
        /**
          * @var $attributes \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
@endsection