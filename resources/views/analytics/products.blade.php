@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Products') }}</h2>
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
    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>{{ __('Whoops!') }}</strong> {{ __('There were some problems with your input.') }}<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="col-12">
        @can('analytics-products-list')
        {!! Form::open(['route' => 'analytics.products.csv.import', 'method'=>'POST', 'files' => true, 'class' => 'form-group pull-right border-left border-dark pl-3 ml-3']) !!}
        {!! Form::hidden('without_operation', 1) !!}
        {!! Form::submit(__('Import from CSV'), ['class' => 'btn btn-sm btn-warning pull-right']) !!}
        {!! Form::file('csv_file', ['id' => 'csv_file', 'class' => 'form-control-file pull-right w-auto', 'required' => true, 'accept' => '.csv']) !!}
        {!! Form::close() !!}
        <div class="pull-right">
            <a class="btn btn-sm btn-success"
               href="{{ route('analytics.products.csv.get') }}"> {{ __('Download products in CSV') }}</a>
        </div>
        @endcan
    </div>
    <table class="table table-light table-bordered table-responsive-sm">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>@sortablelink('name', __('Name'))</th>
            <th>@sortablelink('reference', __('Reference'))</th>
            <th>{{ __('Manufacturer') }}</th>
            <th>{{ __('Composite') }}</th>
            <th>{{ __('Guarantee') }}</th>
            @can('analytics-products-list')<th>{{ __('Wholesale Price') }}</th>@endcan
            <th></th>
        </tr>
        </thead>
        <tbody>
        {{ Form::open(array('route' => 'analytics.products.show','method'=>'GET')) }}
        <tr>
            <td></td>
            <td class="p-1">{{ Form::text('filter-name', Request::input('filter-name'), ['class' => 'form-control']) }}</td>
            <td class="p-1">{{ Form::text('filter-reference', Request::input('filter-reference'), ['class' => 'form-control']) }}</td>
            <td></td>
            <td></td>
            <td></td>
            @can('analytics-products-list')<td></td>@endcan
            <td class="text-right p-1">{{ Form::button('<i class="fa fa-search"></i>', ['class' => 'btn', 'type' => 'submit']) }}
                <a class="btn"
                   href="{{ route('analytics.products.show') }}"><i class="fa fa-close"></i></a>
            </td>
        </tr>
        {{Form::close()}}
        @foreach ($products as $key => $product)
            <tr>
                <td>{{ $product->id }}</td>
                <td>
                    {{ $product->name }}
                    @if ($product->isComposite())
                        <div class="clear">
                            @foreach($product->products()->pluck('name') as $v)
                                <label class="badge badge-success">{{ $v }}</label>
                            @endforeach
                        </div>
                    @endif
                </td>
                <td>{{ $product->reference }}</td>
                <td>{{ $product->manufacturer->name }}</td>
                <td class="text-center"><i class="fa @if ($product->isComposite()) fa-check @else fa-close @endif"></i>
                </td>
                <td class="text-center"><i class="fa @if ($product->need_guarantee) fa-check @else fa-close @endif"></i>
                </td>
                @can('analytics-products-list')<td>{{ $product->getPrice() }}</td>@endcan
                <td></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $products \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $products->render() !!}
@endsection