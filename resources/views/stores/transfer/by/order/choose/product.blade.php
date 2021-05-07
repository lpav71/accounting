@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Transfer product') }}</h2>
                <h2>{{ __('From store:') }} {{ $store->name }}</h2>
                <h2>{{ __('To store:') }} {{ $toStore->name }}</h2>
                <h2>{{ __('By order:') }} {{ $order->getDisplayNumber() }}</h2>
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
    {!! Form::model($store, ['method' => 'PATCH','route' => ['stores.transfer.by.order', $store->id]]) !!}
    {!! Form::hidden('order_id', $order->id) !!}
    {!! Form::hidden('store_id', $toStore->id) !!}
    {!! Form::hidden('comment', $comment) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('order_detail_id[]', __('Product').':') !!}
                {!! Form::select(
                    'order_detail_id[]',
                    $orderDetails,
                    null,
                    ['multiple' => true, 'class' => 'form-control selectpicker']
                ) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary store-transfer-submit-button">{{ __('Transfer it') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection