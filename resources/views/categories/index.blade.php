@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Categories Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('categories-create')
                    <a class="btn btn-sm btn-success" href="{{ route('categories.create') }}"> {{ __('Create New category') }}</a>
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
            <th>{{ __('Parent category') }}</th>
            <th>{{ __('Default') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($categories as $key => $category)
            <tr>
                <td>{{ $category->id }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ isset($category->parentCategory->name) ? $category->parentCategory->name :'' }}</td>
                <td class="text-center">{!! Form::checkbox('',1, $category->is_default, array('class' => 'form-control','disabled')) !!}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('categories.show',$category->id) }}">{{ __('Show') }}</a>
                            @can('categories-edit')
                                <a class="dropdown-item" href="{{ route('categories.edit',$category->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('categories-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['categories.destroy', $category->id],'style'=>'display:inline']) !!}
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
          * @var $categories \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $categories->render() !!}
@endsection