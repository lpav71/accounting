@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Customers Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('customer-create')
                    <a class="btn btn-sm btn-success" href="{{ route('customers.create') }}"> {{ __('Create New Customer') }}</a>
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
    {{ Form::open(['route' => 'customers.index','method'=>'GET']) }}
    <table class="table table-light table-bordered table-responsive-sm">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('First Name') }}</th>
            <th>{{ __('Last Name') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('E-mail') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
                <tr id="searchCustomers"> 
                    <td class="p-0">{{ Form::text('id', Request::input('id'), ['class' => 'form-control form-control-sm search-input text-center', 'size' => 2]) }}</td>
                    <td class="p-0">{{ Form::select('first_name', \App\Customer::distinct()->orderBy('first_name')->pluck('first_name')->prepend('--', 0) ,Request::input('first_name'), ['class' => 'form-control form-control-sm selectpicker-searchable']) }}</td>
                    <td class="p-0">{{ Form::select('last_name', \App\Customer::distinct()->orderBy('last_name')->pluck('last_name')->prepend('--', 0) ,Request::input('last_name'), ['class' => 'form-control form-control-sm selectpicker-searchable']) }}</td>
                    <td class="p-0">{{ Form::select('phone', \App\Customer::distinct()->orderBy('phone')->pluck('phone')->prepend('--', 0) ,Request::input('phone'), ['class' => 'form-control form-control-sm selectpicker-searchable']) }}</td>
                    <td class="p-0">{{ Form::select('email', \App\Customer::distinct()->orderBy('email')->pluck('email')->prepend('--', 0) ,Request::input('email'), ['class' => 'form-control form-control-sm selectpicker-searchable']) }}</td>
                    <td class="text-center p-0 align-middle border-left-0 text-nowrap">
                        {{ Form::button('<i class="fa fa-search"></i>', ['class' => 'btn btn-sm', 'type' => 'submit']) }}
                        <a class="btn btn-sm" href="{{ route('customers.index') }}"><i class="fa fa-close"></i></a>
                    </td>
                <tr>
        @foreach ($customers as $key => $customer)
            <tr>
                <td>{{ $customer->id }}</td>
                <td>{{ $customer->first_name }}</td>
                <td>{{ $customer->last_name }}</td>
                <td>{{ $customer->phone }}</td>
                <td>{{ $customer->email }}</td>
                <td class="text-center">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('customers.show',$customer->id) }}">{{ __('Show') }}</a>
                            @can('customer-edit')
                                <a class="dropdown-item" href="{{ route('customers.edit',$customer->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('customer-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['customers.destroy', $customer->id],'style'=>'display:inline']) !!}
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
    {{Form::close()}}
    @php
        {{
        /**
          * @var $customers \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $customers->render() !!}
@endsection