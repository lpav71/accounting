@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('View rules') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('substitute.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>Что искать</strong>
                {{ $substitute->find }} <br>
                <strong>Чем заменить</strong>
                {{ $substitute->replace }}
            </div>
        </div>
    </div>
@endsection