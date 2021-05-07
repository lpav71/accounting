@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Product') }}</h2>
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



    {!! Form::open(array('route' => 'products.store','method'=>'POST')) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, array('placeholder' => __('Name for accounting'),'class' => 'form-control', 'required')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
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
                {!! Form::select('manufacturer_id', $manufacturers, [], ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('category_id', __('Category')) !!}
                {!! Form::select('category_id', $categories, [], ['class' => 'form-control selectpicker']) !!}
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
                {!! Form::checkbox('is_composite', 1, null, ['class' => 'form-check-input']) !!}
                {!! Form::label('is_composite', __('Composite product'), ['class' => 'form-check-label']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group" id="part-products">
                {!! Form::label('products[]', __('Products:')) !!}
                {!! Form::select('products[]', $products, [], ['class' => 'form-control selectpicker-searchable','multiple']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection