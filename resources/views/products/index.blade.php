@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Products Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('product-create')
                    <a class="btn btn-sm btn-success"
                       href="{{ route('products.csv.downloadСSV') }}"> {{ __('Download products in CSV') }}</a>
                    <a class="btn btn-sm btn-warning"
                       href="{{ route('products.csv.get') }}"> {{ __('Import from CSV') }}</a>
                    <a class="btn btn-sm btn-success"
                       href="{{ route('products.create') }}"> {{ __('Create New Product') }}</a>
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
    <table class="table table-light table-bordered table-responsive-sm small">
        <thead class="thead-light">

        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Picture') }}</th>
            <th>@sortablelink('name', __('Name'))</th>
            <th>@sortablelink('reference', __('Reference'))</th>
            <th>{{ __('Manufacturer') }}</th>
            <th>{{ __('Composite') }}</th>
            <th>{{ __('Guarantee') }}</th>
            <th></th>
            @foreach($stores as $store)
                <th>{{ $store->name }}</th>
                <th class="bg-light">{{ $store->name }}<br><span class="text-nowrap">[{{ __('Reserved') }}]</span></th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        {{ Form::open(array('route' => 'products.index','method'=>'GET')) }}
        <tr>
            <td><div id="select-all" class="btn btn-secondary">{{__('All')}}</div></td>
            <td></td>
            <td class="p-1">{{ Form::text('filter-name', Request::input('filter-name'), ['class' => 'form-control']) }}</td>
            <td class="p-1">{{ Form::text('filter-reference', Request::input('filter-reference'), ['class' => 'form-control']) }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td class="text-right p-1">{{ Form::button('<i class="fa fa-search"></i>', ['class' => 'btn', 'type' => 'submit']) }}
                <a class="btn"
                   href="{{ route('products.index') }}"><i class="fa fa-close"></i></a>
            </td>
            @foreach($stores as $store)
                <td></td>
                <td></td>
            @endforeach
        </tr>
        {{Form::close()}}
        @can('product-edit')
            {{ Form::open(array('route' => 'products.mass.process','method'=>'POST')) }}
        @endcan
        @foreach ($products as $key => $product)
            <tr>
                <td>
                        <div class="custom-control custom-checkbox d-flex align-items-center">
                            @can('product-edit')
                            {!! Form::checkbox('products[]',  $product->id , false, ['class' => 'form-check-input mt-0 product-select']) !!}
                            @endcan
                            <span>{{$product->id}}</span>
                        </div>
                </td>
                @if (!empty($product->mainPicture()))
                <td class="pl-0 pr-0"><img class="w-100" src="{{$product->mainPicture()->url}}" alt="{{$product->id}}"></td>
                @else
                <td class="pl-0 pr-0"></td>
                @endif
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
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" 
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item"
                               href="{{ route('products.show',$product->id) }}">{{ __('Show') }}</a>
                            @can('product-edit')
                                <a class="dropdown-item"
                                   href="{{ route('products.edit',$product->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('product-delete')
                                {!! Form::button(__('Delete'), ['class' => 'dropdown-item delete-product', 'data-del-url'=>route('products.destroy',['id'=>$product->id])]) !!}
                            @endcan
                        </div>
                    </div>
                </td>
                @foreach($stores as $store)
                    @php
                        {{
                        /**
                          * @var $product \App\Product
                          **/
                        }}
                    @endphp
                    <td class="text-center">{{ $product->getCombinedQuantity($store) }}</td>
                    <td class="text-center bg-info">{{ $product->getReservedCombinedQuantity($store) }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
    @can('product-edit')
    <div class="mb-4">
        <div class="form-group">
            <select name="action" class="form-control">
                <option value="">{{__('Choose action')}}</option>
                <option value="disable-products">{{__('Disable')}}</option>
                <option value="enable-products">{{__('Enable')}}</option>
            </select>
            {!! Form::select('channels[]', \App\Channel::whereContentControl()->orderBy('id','desc')->pluck('name','id'), null , ['title'=>__('All channels'), 'multiple' => true, 'class' => 'form-control mt-2']) !!}
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
    </div>    
    {{Form::close()}}
    @endcan
    @php
        {{
        /**
          * @var $products \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $products->render() !!}
@endsection