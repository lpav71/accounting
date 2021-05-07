@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Cities management') }}</h2>
            </div>
            <div class="pull-right">
                @can('carrier-create')
                    <a class="btn btn-sm btn-success" href="{{ route('city.create') }}"> {{ __('Create new city') }}</a>
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
            <th>{{__('X coordinate')}}
            <th>{{__('Y coordinate')}}
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($cities as $key => $city)
            <tr>
                <td>{{ $city->id }}</td>
                <td>{{ $city->name }}</td>
                <td>{{ $city->x_coordinate }}</td>
                <td>{{ $city->y_coordinate }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('city.show',$city->id) }}">{{ __('Show') }}</a>
                            @can('carrier-edit')
                                <a class="dropdown-item" href="{{ route('city.edit',$city->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('carrier-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['city.destroy', $city->id],'style'=>'display:inline']) !!}
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
          * @var $cities \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $cities->render() !!}
@endsection