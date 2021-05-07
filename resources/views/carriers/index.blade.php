@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Carriers Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('carrier-create')
                    <a class="btn btn-sm btn-success" href="{{ route('carriers.create') }}"> {{ __('Create New Carrier') }}</a>
                @endcan
            </div>
            <div class="pull-right pr-2">
                @can('carrier-types-list')
                    <a class="btn btn-sm btn-success" href="{{ route('carrier-types.index') }}"> {{ __('Carrier types') }}</a>
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
            <th>{{__('Carrier type')}}
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($carriers as $key => $carrier)
            <tr>
                <td>{{ $carrier->id }}</td>
                <td>{{ $carrier->name }}</td>
                <td>{{ isset($carrier->carrier_type->name) ? $carrier->carrier_type->name : '' }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('carriers.show',$carrier->id) }}">{{ __('Show') }}</a>
                            @can('carrier-edit')
                                <a class="dropdown-item" href="{{ route('carriers.edit',$carrier->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('carrier-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['carriers.destroy', $carrier->id],'style'=>'display:inline']) !!}
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
    {!! $carriers->render() !!}
@endsection