@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Show Store') }}</h2>
            </div>
            @can('store-list')
                <div class="pull-right">
                    <a class="btn btn-primary" href="{{ route('stores.index') }}"> {{ __('Back') }}</a>
                </div>
            @endcan
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
    <div class="row title-block pb-0">
        <div class="row col-md-6 col-sm-12">
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>{{ __('Name:') }}</strong>
                    {{ $store->name }}
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>{{ __('Users:') }}</strong>
                    @if(!empty($store->users))
                        @foreach($store->users as $v)
                            <label class="badge badge-success">{{ $v->name }}</label>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            @can('store-edit')
                {!! Form::open(['route' => ['stores.csv.import', $store], 'method'=>'POST', 'files' => true, 'class' => 'form-group pull-right row']) !!}
                <div class="col-md-10">
                    <div class="input-group input-group-sm pull-right">
                        <div class="input-group-prepend">
                            {!! Form::select('operation_type', ['D' => __('Debit'), 'I' => __('Inventory'), 'ICHUNK' => __('Chunk Inventory')],'D', ['class' => 'form-control form-control-sm']) !!}
                        </div>
                        <div class="ml-1">
                            {!! Form::file('csv_file', ['id' => 'csv_file', 'class' => 'form-control-file pull-right w-auto', 'required' => true, 'accept' => '.csv']) !!}
                        </div>

                    </div>
                    <div>
                        {!! Form::text('comment', null, array('placeholder' => __('Comment'),'class' => 'form-control', 'required')) !!}
                    </div>
                </div>
                <div class="input-group-append col-md-2">
                    {!! Form::submit(__('Import from CSV'), ['class' => 'btn btn-sm btn-warning pull-right']) !!}
                </div>
                {!! Form::close() !!}
            @endcan
        </div>
        @can('store-operation')
            @if($store->users()->find(Auth::user()->id))
                <div class="col-12 title-block mb-3">
                    <div class="pull-right">
                        <a class="btn btn-sm btn-success"
                           href="{{ route('store-operations.create', ['store' => $store]) }}"> {{ __('Create New Operation') }}</a>
                    </div>
                    <div class="pull-right">
                        <a class="btn btn-sm btn-info mr-3"
                           href="{{ route('stores.transfer.csv', ['store' => $store]) }}"> {{ __('Transfer products (CSV)') }}</a>
                    </div>
                    <div class="pull-right">
                        <a class="btn btn-sm btn-info mr-3"
                           href="{{ route('stores.transfer', ['store' => $store]) }}"> {{ __('Transfer product') }}</a>
                    </div>
                    <div class="pull-right">
                        <a class="btn btn-sm btn-info mr-3"
                           href="{{ route('stores.transfer.multi', ['store' => $store]) }}"> {{ __('Multi Transfer products') }}</a>
                    </div>
                    <div class="pull-right">
                        <a class="btn btn-sm btn-info mr-3"
                           href="{{ route('stores.transfer.by.order.order', ['store' => $store]) }}"> {{ __('Transfer product by order') }}</a>
                    </div>
                </div>
            @endif
        @endcan
        <div class="col-12 pb-2">
            <div class="pull-right">
                <a class="btn btn-sm btn-success ml-1"
                   href="{{ route('stores.current.csv.get', ['store' => $store]) }}"> {{ __('Download current balance in CSV') }}</a>
            </div>
            <div class="pull-right">
                <a class="btn btn-sm btn-success"
                   href="{{ route('stores.csv.get', ['store' => $store]) }}"> {{ __('Download balance in CSV') }}</a>
            </div>
            <div class="pull-right">
                <a class="btn btn-sm btn-info mr-3"
                   href="{{ route('stores.csv.dictionary') }}"> {{ __('Download Reference dictionary in CSV') }}</a>
            </div>
            <div class="pull-right">
                <a class="mr-3"
                   href="{{ route('stores.current.products', $store) }}"> {{ __('Free rest') }}</a>
            </div>
            <div class="pull-right">
                <a class="mr-3"
                   href="{{ route('stores.current.products.brands', ['store'=>$store->id]) }}"> {{ __('Free rest by brands') }}</a>
            </div>
            <div class="pull-right">
                <a class="mr-3"
                    href="{{ route('stores.full.products', $store) }}"> {{ __('Full rest') }}</a>
            </div>
            <div class="pull-right">
                <a class="mr-3"
                    href="{{ route('stores.find-reserve', ['store' => $store]) }}"> {{ __('Find reserved by ref') }}</a>
            </div>
        </div>
    </div>
    @can('store-list'){{ Form::open(['route' => ['stores.show', $store],'method'=>'GET']) }}@else{{ Form::open(['route' => ['own-stores.show', $store],'method'=>'GET']) }}@endcan
    <table class="table table-light table-bordered table-responsive-xs margin-tb">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Product') }}</th>
            <th>{{ __('Quantity') }}</th>
            <th class="text-nowrap">{{ __('Free rest') }}</th>
            <th class="text-nowrap">{{ __('Full rest') }}</th>
            <th>{{ __('Comment') }}</th>
            <th>{{ __('Order') }}</th>
            <th>{{ __('User') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr id="searchOperations">
            <td class="p-0" area-label="{{ __('Id') }}"></td>
            <td class="p-0"
                area-label="{{ __('Date') }}"></td>
            <td class="p-0"
                area-label="{{ __('Type') }}"></td>
            <td class="p-0"
                area-label="{{ __('Product') }}">{{ Form::select('operableId', \App\Product::all()->pluck('name', 'id')->prepend('--', 0) ,Request::input('operableId'), ['class' => 'form-control form-control-sm selectpicker-searchable']) }}</td>
            <td class="p-0"
                area-label="{{ __('Quantity') }}"></td>
            <td class="p-0"
                area-label="{{ __('Free rest') }}"></td>
            <td class="p-0"
                area-label="{{ __('Full rest') }}"></td>
            <td class="p-0"
                area-label="{{ __('Comment') }}"></td>
            <td class="p-0"
                area-label="{{ __('Order') }}"></td>
            <td class="p-0 text-center"
                area-label="{{ __('User') }}">{{ Form::button('<i class="fa fa-search"></i>', ['class' => 'btn btn-sm', 'type' => 'submit']) }}
                <a class="btn btn-sm"
                   href="@can('store-list'){{ route('stores.show', ['store' => $store]) }}@else{{ route('own-stores.show', ['store' => $store]) }}@endcan"><i
                            class="fa fa-close"></i></a></td>
        </tr>
        @foreach ($operations as $key => $operation)
            <tr>
                <td>{{ $operation->id }}</td>
                <td>{{ $operation->created_at }}</td>
                <td>{{ $operation->type == 'C' ? __('Credit') : __('Debit') }}{{ $operation->is_reservation ? '*' : '' }}</td>
                <td>{{ $operation->operable->name }}</td>
                <td>{{ $operation->quantity }}</td>
                <td>{{ $operation->operable->getCombinedQuantityAfterOperation($operation->storage, $operation) }}</td>
                <td>{{ $operation->operable->getRealCombinedQuantityAfterOperation($operation->storage, $operation) }}</td>
                <td>{{ $operation->comment }}</td>
                <td>{{ $operation->order ? $operation->order->getDisplayNumber() : '' }}</td>
                <td>{{ $operation->user->name }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{Form::close()}}
    @php
        {{
        /**
          * @var $operations \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $operations->render() !!}
@endsection