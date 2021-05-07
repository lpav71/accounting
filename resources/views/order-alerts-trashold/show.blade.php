@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Show Order Alert') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('order-alerts.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('Trashold:') }}</strong>
                {{ $orderAlert->trashold }}
            </div>
        </div>
    </div>
@endsection