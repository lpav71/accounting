@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Characteristics management') }}</h2>
            </div>
            <div class="pull-right">
                @can('product-characteristics-create')
                    <a class="btn btn-sm btn-success" href="{{ route('product-characteristics.create') }}"> {{ __('Create new characteristic') }}</a>
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
        @foreach ($characteristics as $key => $characteristic)
            <tr>
                <td>{{ $characteristic->id }}</td>
                <td>{{ $characteristic->name }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('product-characteristics.show',$characteristic->id) }}">{{ __('Show') }}</a>
                            @can('product-characteristics-edit')
                                <a class="dropdown-item" href="{{ route('product-characteristics.edit',$characteristic->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('product-characteristics-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['product-characteristics.destroy', $characteristic->id],'style'=>'display:inline']) !!}
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
          * @var $characteristics \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $characteristics->render() !!}
@endsection