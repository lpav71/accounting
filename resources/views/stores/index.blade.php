@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Stores Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('store-create')
                    <a class="btn btn-sm btn-success" href="{{ route('stores.create') }}"> {{ __('Create New Store') }}</a>
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
    <table class="table table-striped table-bordered table-responsive-xs">
        <thead class="thead-light">
        <tr>
            <th class="text-nowrap">@sortablelink('id', __('Id'))</th>
            <th>@sortablelink('name', __('Name'))</th>
            <th>{{ __('Users') }}</th>
            <th>{{ __('Operations') }}</th>
            <th>{{ __('Reservations') }}</th>
            <th>{{ __('Transfers') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($stores as $key => $store)
            <tr>
                <td>{{ $store->id }}</td>
                <td>{{ $store->name }}</td>
                <td>
                    @if(!empty($store->users))
                        @foreach($store->users as $v)
                            <label class="badge badge-success">{{ $v->name }}</label>
                        @endforeach
                    @endif
                </td>
                <td>
                    @if(!empty($store->usersWithOperationRights))
                        @foreach($store->usersWithOperationRights as $v)
                            <label class="badge badge-success">{{ $v->name }}</label>
                        @endforeach
                    @endif
                </td>
                <td>
                    @if(!empty($store->usersWithReservationRights))
                        @foreach($store->usersWithReservationRights as $v)
                            <label class="badge badge-success">{{ $v->name }}</label>
                        @endforeach
                    @endif
                </td>
                <td>
                    @if(!empty($store->usersWithTransferRights))
                        @foreach($store->usersWithTransferRights as $v)
                            <label class="badge badge-success">{{ $v->name }}</label>
                        @endforeach
                    @endif
                </td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('stores.show',$store->id) }}">{{ __('Show') }}</a>
                            @can('store-edit')
                                <a class="dropdown-item" href="{{ route('stores.edit',$store->id) }}">{{ __('Edit') }}</a>
                                @if(!$store->is_hidden)
                                <a class="dropdown-item" href="{{ route('store.hide-in-menu',$store->id) }}">{{ __('Hide in menu') }}</a>
                                @elseif($store->is_hidden)
                                <a class="dropdown-item" href="{{ route('store.show-in-menu',$store->id) }}">{{ __('Show in menu') }}</a>
                                @endif
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
          * @var $stores \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $stores->render() !!}
@endsection