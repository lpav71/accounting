@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Show city') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('city.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>{{ __('Name:') }}</strong>
                {{ $city->name }}
                <strong>{{ __('X coordinate') }}</strong>
                {{ $city->x_coordinate }}
                <strong>{{ __('Y coordinate') }}</strong>
                {{ $city->y_coordinate }}
            </div>
        </div>
    </div>
@endsection