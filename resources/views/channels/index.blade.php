@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Channels Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('channel-create')
                    <a class="btn btn-sm btn-success" href="{{ route('channels.create') }}"> {{ __('Create New Channel') }}</a>
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
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($channels as $key => $channel)
            <tr>
                <td>{{ $channel->id }}</td>
                <td>{{ $channel->name }}</td>
                <td class="text-center">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('channels.show',$channel->id) }}">{{ __('Show') }}</a>
                            @can('channel-edit')
                                <a class="dropdown-item" href="{{ route('channels.edit',$channel->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('notification-template-list')
                            <a class="dropdown-item" href="{{ route('notificationTemplate.index',$channel->id) }}">{{ __('Notifications templates') }}</a>
                            @endcan()
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
          * @var $channels \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $channels->render() !!}
@endsection
