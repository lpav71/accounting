@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Ticket event subscriptions management') }}</h2>
            </div>
            <div class="pull-right">
                @can('ticketEventSubscriptions-create')
                    <a class="btn btn-sm btn-success"
                       href="{{ route('ticket-event-subscriptions.create') }}"> {{ __('Create new ticket event subscription') }}</a>
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
    <table class="table table-light table-bordered table-responsive-xs">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Criteria') }}</th>
            <th>{{ __('Actions') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($ticketEventSubscriptions as $key => $ticketEventSubscription)
            <tr>
                <td>{{ $ticketEventSubscription->id }}</td>
                <td>{{ $ticketEventSubscription->name }}</td>
                <td>
                    @foreach($ticketEventSubscription->ticketEventCriteria as $ticketEventCriterion)
                        <label class="badge badge-success">{{ $ticketEventCriterion->name }}</label>
                    @endforeach
                </td>
                <td>
                    @foreach($ticketEventSubscription->ticketEventActions as $ticketEventAction)
                        <label class="badge badge-success">{{ $ticketEventAction->name }}</label>
                    @endforeach
                </td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('ticketEventSubscriptions-edit')
                                <a class="dropdown-item"
                                   href="{{ route('ticket-event-subscriptions.edit',$ticketEventSubscription->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('ticketEventSubscriptions-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['ticket-event-subscriptions.destroy', $ticketEventSubscription->id],'style'=>'display:inline']) !!}
                                {!! Form::button(__('Delete'), ['class' => 'dropdown-item', 'type' => 'submit']) !!}
                                {!! Form::close() !!}
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $attributes \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $ticketEventSubscriptions->render() !!}
@endsection