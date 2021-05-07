@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Carrier types') }}</h2>
            </div>
            <div class="pull-right">
                @can('carrier-create')
                    <a class="btn btn-sm btn-success" href="{{ route('carrier-types.create') }}"> {{ __('Create New Carrier type') }}</a>
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
        @foreach ($carrierTypes as $key => $type)
            <tr>
                <td>{{ $type->id }}</td>
                <td>{{ $type->name }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('carrier-types.show',$type->id) }}">{{ __('Show') }}</a>
                            @can('carrier-edit')
                                <a class="dropdown-item" href="{{ route('carrier-types.edit',$type->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('carrier-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['carrier-types.destroy', $type->id],'style'=>'display:inline']) !!}
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
    {!! $carrierTypes->render() !!}
@endsection