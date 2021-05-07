@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Show Order Detail State') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('order-detail-states.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('Name:') }}</strong>
                {{ $orderDetailState->name }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('Previous state:') }}</strong>
                {{ $orderDetailState->previousState ? $orderDetailState->previousState->name : '' }}
            </div>
        </div>
    </div>
@endsection