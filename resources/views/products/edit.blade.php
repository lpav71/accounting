@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit Product') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('products.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
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
    @if($product->is_blocked)
        <div class="alert alert-danger">
            <h5>{{__('PRODUCT IS BLOCKED')}}</h5>
        </div>
    @endif
    {!! Form::model($product, ['method' => 'PATCH','route' => ['products.update', $product->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, array('placeholder' => __('Name for accounting'),'class' => 'form-control', 'required')) !!}
            </div>
        </div>
        <div class="col-12">
            <div title="asd" class="form-group">
                {!! Form::label('title', __('Title')) !!}
                {!! Form::text('title', null, array('placeholder' => __('Title for channel'),'class' => 'form-control', 'required')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('reference', __('Reference:')) !!}
                {!! Form::text('reference', null, array('placeholder' => __('Reference'),'class' => 'form-control', 'required')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('ean', __('EAN:')) !!}
                {!! Form::text('ean', null, array('placeholder' => __('EAN'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('manufacturer_id', __('Manufacturer:')) !!}
                {!! Form::select('manufacturer_id', $manufacturers, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
                <div class="form-group">
                    {!! Form::label('category_id', __('Category')) !!}
                    {!! Form::select('category_id', $categories, null, ['class' => 'form-control selectpicker']) !!}
                </div>
            </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('need_guarantee', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('need_guarantee', __('Guarantee available'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_blocked', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_blocked', __('PRODUCT IS BLOCKED'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group" id="product-combination">
                {!! Form::label('product_combination', __('Add to product in combination')) !!}
                {!! Form::select('product_combination', $products->prepend(__('Delete from combination'), -1)->prepend('--', 0), null, ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
            <div>
                @if ($product->combination)
                    @foreach ($product->combination->products as $item)
                        <li><a href="{{route('products.edit',['id'=>$item->id])}}">{{ $item->name }}</a></li>
                    @endforeach
                @endif
            <div>
        </div>
        <div class="col-12">
                <div class="card">
                        <p class="m-1">
                            <button class="btn btn-light" type="button" data-toggle="collapse" data-target="#attributes" aria-expanded="false" aria-controls="collapseExample">
                                {{__('Attributes')}}
                            </button>
                        </p>
                        <div class="collapse" id="attributes">
                            <div class="card card-block p-2">
                <table class="table">
                        <thead>
                          <tr>
                            <th scope="col-md-3">{{__('Group attribute')}}</th>
                            <th scope="col-md-3">{{__('Value')}}</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($attributes as $attribute)
                            <tr>
                                    <td scope="row">{{$attribute->name}}</td>
                                    <td>
                                        {!! Form::text('attributes['.$attribute->id.']', $attribute->attr_value, ['class' => 'form-control']) !!}           
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <hr>
        </div>
        <div class="col-12">
                <div class="card">
                        <p class="m-1">
                            <button class="btn btn-light" type="button" data-toggle="collapse" data-target="#characteristics" aria-expanded="false" aria-controls="collapseExample">
                                {{__('Characteristics')}}
                            </button>
                        </p>
                        <div class="collapse" id="characteristics">
                            <div class="card card-block p-2">
                <table class="table">
                        <thead>
                          <tr>
                            <th scope="col-md-3">{{__('Characteristics')}}</th>
                            <th scope="col-md-3">{{__('Value')}}</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($characteristics as $characteristic)
                            <tr>
                                    <td scope="row">{{$characteristic->name}}</td>
                                    <td>
                                        {!! Form::text('characteristic['.$characteristic->id.']', $characteristic->attr_value, ['class' => 'form-control']) !!}           
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <hr>
        </div>
        @if (!$product->isUsedInOperations() && !$product->isUsedInOrders())
        <div class="col-12">
            <div class="form-group form-check">
                {!! Form::checkbox('is_composite', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_composite', __('Composite product'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group" id="part-products">
                {!! Form::label('products[]', __('Products:')) !!}
                {!! Form::select('products[]', $products, null, ['class' => 'form-control selectpicker-searchable','multiple']) !!}
            </div>
        </div>
        @endif
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    <br>
    {!! Form::close() !!}
    <div class="file-loading">
        <input data-id="{{$product->id}}" id="product-pictures" name="productPictures[]" type="file" multiple>
    </div>
    <br>
    <br>
    <div>
        <h3>{{__('Channel`s product')}}</h3>
        <div id="presta_product" data-id="{{$product->id}}">
            <!-- presta_product.js -->
        </div>
    <div>
    <div class="row">
        <div class="col-12">
            <div class="form-group">
            <a href="{{route('products.copy',['id' => $product->id])}}">{{__('Copy product')}}</a>
            </div>
        </div>
    </div>
@endsection