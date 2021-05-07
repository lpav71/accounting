@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Carrier groups management') }}</h2>
            </div>
            <div class="pull-right">
                @can('carrier-create')
                    <a class="btn btn-sm btn-success" href="{{ route('carrier-group.create') }}"> {{ __('Create new carrier group') }}</a>
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
            <th>{{ __('Carriers') }}</th>
            <th>{{ __('Users') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($carrier_groups as $key => $group)
            <tr>
                <td>{{ $group->id }}</td>
                <td>{{ $group->name }}</td>
                <td>
                    @if(!empty($group->carriers))
                        @foreach($group->carriers as $carrier)
                            <label class="badge badge-success">{{ $carrier->name }}</label>
                        @endforeach
                    @endif
                </td>
                <td>
                    @if(!empty($group->users))
                        @foreach($group->users as $user)
                            <label class="badge badge-success">{{ $user->name }}</label>
                        @endforeach
                    @endif
                </td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('carrier-edit')
                                <a class="dropdown-item" href="{{ route('carrier-group.edit',$group->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('carrier-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['carrier-group.destroy', $group->id],'style'=>'display:inline']) !!}
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
    {!! $carrier_groups->render() !!}
@endsection