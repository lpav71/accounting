@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Ticket priorities management') }}</h2>
            </div>
            <div class="pull-right">
                @can('ticketPriority-create')
                    <a class="btn btn-sm btn-success"
                       href="{{ route('ticket-priorities.create') }}"> {{ __('Create new ticket priority') }}</a>
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
            <th>{{ __('Rate') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($ticketPriorities as $key => $ticketPriority)
            <tr>
                <td>{{ $ticketPriority->id }}</td>
                <td>{{ $ticketPriority->name }}</td>
                <td>{{ $ticketPriority->rate }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('ticketTheme-edit')
                                <a class="dropdown-item"
                                   href="{{ route('ticket-priorities.edit',$ticketPriority->id) }}">{{ __('Edit') }}</a>
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
    {!! $ticketPriorities->render() !!}
@endsection