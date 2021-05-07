@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Transfer product from store:') }} {{ $store->name }}</h2>
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
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    {!! Form::model($store, ['method' => 'POST','route' => ['stores.transfer.by.order.product', $store->id]]) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('store_id', __('To store').':') !!}
                {!! Form::select('store_id', $stores, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('order_id', __('Order').':') !!}
                {!! Form::select('order_id', $orders, null, ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('comment', __('Comment:')) !!}
                {!! Form::textarea('comment', null, ['class' => 'form-control', 'rows' => 4]) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Next') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection