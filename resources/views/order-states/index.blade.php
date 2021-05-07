@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Order States Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('orderState-create')
                    <a class="btn btn-sm btn-success" href="{{ route('order-states.create') }}"> {{ __('Create New Order State') }}</a>
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
    <table class="table table-light table-bordered table-responsive-sm">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Previous state') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($orderStates as $key => $orderState)
            <tr>
                <td>{{ $orderState->id }}</td>
                <td>{{ $orderState->name }}</td>
                <td>
                    @foreach($orderState->previousStates as $previousState)
                        <label class="badge badge-success">{{ $previousState->name }}</label>
                    @endforeach
                </td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('order-states.show',$orderState->id) }}">{{ __('Show') }}</a>
                            @can('orderState-edit')
                                <a class="dropdown-item" href="{{ route('order-states.edit',$orderState->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('orderState-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['order-states.destroy', $orderState->id],'style'=>'display:inline']) !!}
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
          * @var $orderStates \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $orderStates->render() !!}
@endsection