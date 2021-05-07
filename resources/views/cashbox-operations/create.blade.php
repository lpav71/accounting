@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Cashbox Operation') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route((Auth::user()->hasPermissionTo('cashbox-list') ? '' : 'own-') . 'cashboxes.show', ['store' => $cashbox]) }}"> {{ __('Back') }}</a>
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



    {!! Form::open(['route' => ['cashbox-operations.store', $cashbox],'method'=>'POST']) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('cashbox_name', __('Cashbox:')) !!}
                {!! Form::text('cashbox_name', $cashbox->name, ['class' => 'form-control', 'disabled' => true]) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('currency_id', __('Currency:')) !!}
                {!! Form::select('currency_id', $currencies, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('quantity', __('Sum:')) !!}
                {!! Form::text('quantity', null, ['placeholder' => __('Sum'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('type', __('Type:')) !!}
                {!! Form::select('type', ['D'=>__('Debit'), 'C'=>__('Credit')], [], ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('order_id', __('Order:')) !!}
                {!! Form::select('order_id', $orders, null, ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('product_return_id', __('Return:')) !!}
                {!! Form::select('product_return_id', $productReturns, null, ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('product_exchange_id', __('Exchange:')) !!}
                {!! Form::select('product_exchange_id', $productExchanges, null, ['class' => 'form-control selectpicker-searchable']) !!}
            </div>
        </div>
        @if($cashbox->for_certificates)
            <div class="col-xs-12 col-sm-6">
                <div class="form-group">
                    {!! Form::label('certificate_id', __('Certificate')) !!}
                    {!! Form::select('certificate_id', $certificates, null, ['class' => 'form-control selectpicker-searchable']) !!}
                </div>
            </div>
        @endif
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