@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Show Currency') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('currencies.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>{{ __('Name:') }}</strong>
                {{ $currency->name }}
            </div>
        </div>
    </div>
@endsection