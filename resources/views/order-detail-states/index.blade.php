@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Order Detail States Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('orderDetailState-create')
                    <a class="btn btn-sm btn-success"
                       href="{{ route('order-detail-states.create') }}"> {{ __('Create New Order Detail State') }}</a>
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
            <th>{{ __('Full Name') }}</th>
            <th>{{ __('Previous state') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($orderDetailStates as $key => $orderDetailState)
            <tr>
                <td>{{ $orderDetailState->id }}</td>
                <td>{{ $orderDetailState->name }}</td>
                <td>{{ $orderDetailState->full_name }}</td>
                <td>
                    @foreach($orderDetailState->previousStates as $previousState)
                        <label class="badge badge-success">{{ $previousState->full_name }}</label>
                    @endforeach
                </td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item"
                               href="{{ route('order-detail-states.show',$orderDetailState->id) }}">{{ __('Show') }}</a>
                            @can('orderDetailState-edit')
                                <a class="dropdown-item"
                                   href="{{ route('order-detail-states.edit',$orderDetailState->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('orderDetailState-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['order-detail-states.destroy', $orderDetailState->id],'style'=>'display:inline']) !!}
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
          * @var $orderDetailStates \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $orderDetailStates->render() !!}
@endsection