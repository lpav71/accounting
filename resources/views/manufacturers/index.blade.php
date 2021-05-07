@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Manufacturers Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('manufacturer-create')
                    <a class="btn btn-sm btn-success" href="{{ route('manufacturers.create') }}"> {{ __('Create New Manufacturer') }}</a>
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
        @foreach ($manufacturers as $key => $manufacturer)
            <tr>
                <td>{{ $manufacturer->id }}</td>
                <td>{{ $manufacturer->name }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('manufacturers.show',$manufacturer->id) }}">{{ __('Show') }}</a>
                            @can('manufacturer-edit')
                                <a class="dropdown-item" href="{{ route('manufacturers.edit',$manufacturer->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('manufacturer-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['manufacturers.destroy', $manufacturer->id],'style'=>'display:inline']) !!}
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
          * @var $manufacturers \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $manufacturers->render() !!}
@endsection