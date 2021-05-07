@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Stores auto transfer settings') }}</h2>
            </div>
            <div class="pull-right">
                @can('store-autotransfer-setting-create')
                    <a class="btn btn-sm btn-success"
                       href="{{ route('store-autotransfer-settings.create') }}"> {{ __('Create new store auto transfer setting') }}</a>
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
        @foreach ($storeAutotransferSettings as $key => $storeAutotransferSetting)
            <tr>
                <td>{{ $storeAutotransferSetting->id }}</td>
                <td>{{ $storeAutotransferSetting->name }}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item"
                               href="{{ route('store-autotransfer-settings.show',$storeAutotransferSetting->id) }}">{{ __('Transfer') }}</a>
                            <a class="dropdown-item"
                               href="{{ route('store-autotransfer-settings.show.back',$storeAutotransferSetting->id) }}">{{ __('Transfer back') }}</a>
                            @can('store-autotransfer-setting-edit')
                                <a class="dropdown-item"
                                   href="{{ route('store-autotransfer-settings.edit',$storeAutotransferSetting->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('store-autotransfer-setting-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['store-autotransfer-settings.destroy', $storeAutotransferSetting->id],'style'=>'display:inline']) !!}
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
          * @var $attributes \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $storeAutotransferSettings->render() !!}
@endsection