@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Carrier groups management') }}</h2>
            </div>
            <div class="pull-right">
                @can('telephonyAccounts-create')
                    <a class="btn btn-sm btn-success"
                       href="{{ route('telephony-account-groups.create') }}"> {{ __('Create new telephony account group') }}</a>
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
            <th>{{ __('Accounts') }}</th>
            <th>{{ __('Users') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($telephonyAccountGroups as $key => $group)
            <tr>
                <td>{{ $group->id }}</td>
                <td>{{ $group->name }}</td>
                <td>
                    @foreach($group->telephonyAccounts as $carrier)
                        <label class="badge badge-success">{{ $carrier->name }}</label>
                    @endforeach
                </td>
                <td>
                    @foreach($group->users as $user)
                        <label class="badge badge-success">{{ $user->name }}</label>
                    @endforeach
                </td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('telephonyAccounts-edit')
                                <a class="dropdown-item"
                                   href="{{ route('telephony-account-groups.edit',$group->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('telephonyAccounts-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['telephony-account-groups.destroy', $group->id],'style'=>'display:inline']) !!}
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
          * @var $carriers \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $telephonyAccountGroups->render() !!}
@endsection