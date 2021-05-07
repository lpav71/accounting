@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Ticket states management') }}</h2>
            </div>
            <div class="pull-right">
                @can('ticketState-create')
                    <a class="btn btn-sm btn-success"
                       href="{{ route('ticket-states.create') }}"> {{ __('Create new ticket state') }}</a>
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
            <th>{{ __('Previous state') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($ticketStates as $key => $ticketState)
            <tr>
                <td>{{ $ticketState->id }}</td>
                <td>{{ $ticketState->name }}</td>
                <td>
                    @foreach($ticketState->previousStates as $previousState)
                        <label class="badge badge-success">{{ $previousState->name }}</label>
                    @endforeach
                </td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('ticketState-edit')
                                <a class="dropdown-item"
                                   href="{{ route('ticket-states.edit',$ticketState->id) }}">{{ __('Edit') }}</a>
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
    {!! $ticketStates->render() !!}
@endsection