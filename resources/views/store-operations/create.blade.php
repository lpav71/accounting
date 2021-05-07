@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Store Operation') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route((Auth::user()->hasPermissionTo('store-list') ? '' : 'own-') . 'stores.show', ['store' => $store]) }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>

    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>{{ __('Whoops!') }}</strong> {{ __('There were some problems with your input.') }}<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{!! $error !!}</li>
                @endforeach
            </ul>
        </div>
    @endif



    {!! Form::open(['route' => ['store-operations.store', $store],'method'=>'POST']) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('store_name', __('Store:')) !!}
                {!! Form::text('store_name', $store->name, ['class' => 'form-control', 'disabled' => true]) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group" id="part-products">
                {!! Form::label('product_id', __('Product:')) !!}
                {!! Form::select('product_id', $products, [], ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('quantity', __('Quantity:')) !!}
                {!! Form::text('quantity', null, array('placeholder' => __('Quantity'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('type', __('Type:')) !!}
                {!! Form::select('type', ['D'=>__('Debit'), 'C'=>__('Credit')], [], ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('comment', __('Comment:')) !!}
                {!! Form::textarea('comment', null, ['class' => 'form-control', 'rows' => 4]) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection