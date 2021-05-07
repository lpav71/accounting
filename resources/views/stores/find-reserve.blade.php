@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Find reserved by ref') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('stores.index') }}"> {{ __('Back') }}</a>
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



    {!! Form::open(array('','method'=>'GET')) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('reference', __('Reference')) !!}
                {!! Form::text('reference', Request::input('reference'), array('placeholder' => __('Reference'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            @foreach ($orderDetails as $orderDetail)
                <p>{{__('Order')}} {{$orderDetail->order->order_number}}</p>
            @endforeach
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection