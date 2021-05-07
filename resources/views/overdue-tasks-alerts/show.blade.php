@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Show Overdue Task Alert') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('overdue-tasks.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('Trashold:') }}</strong>
                {{ $overdueTask->trashold }}
            </div>
        </div>
    </div>
@endsection