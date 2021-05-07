@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit Task Trashold') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('overdue-tasks.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($overdueTask, ['method' => 'PATCH','route' => ['overdue-tasks.update', $overdueTask->id]]) !!}
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('trashold', __('Trashold:')) !!}
                {!! Form::text('trashold', null, ['placeholder' => __('Trashold'),'class' => 'form-control', 'required']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    {!! Form::close() !!}
@endsection
