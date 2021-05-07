@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Currencies Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('currency-create')
                    <a class="btn btn-sm btn-success" href="{{ route('currencies.create') }}"> {{ __('Create New Currency') }}</a>
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
            <th>{{ __('Currency rate') }}</th>
            <th>{{ __('Currency ISO code') }}</th>
            <th>{{ __('Default') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($currencies as $key => $currency)
            <tr>
                <td>{{ $currency->id }}</td>
                <td>{{ $currency->name }}</td>
                <td>{{ $currency->currency_rate }}</td>
                <td>{{ $currency->iso_code }}</td>
                <td class="text-center">{!! Form::checkbox('',1, $currency->is_default, array('class' => 'form-control','disabled')) !!}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('currencies.show',$currency->id) }}">{{ __('Show') }}</a>
                            @can('currency-edit')
                                <a class="dropdown-item" href="{{ route('currencies.edit',$currency->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('currency-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['currencies.destroy', $currency->id],'style'=>'display:inline']) !!}
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
          * @var $currencies \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $currencies->render() !!}
@endsection