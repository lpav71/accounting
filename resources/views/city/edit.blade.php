@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit city') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('city.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($city, ['method' => 'PATCH','route' => ['city.update', $city->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name').':') !!}
                {!! Form::text('name', $city->name, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('x_coordinate', __('X coordinate')) !!}
                {!! Form::number('x_coordinate', $city->x_coordinate, ['placeholder' => __('X coordinate'),'class' => 'form-control', 'step' => '0.00000000000001']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('y_coordinate', __('Y coordinate')) !!}
                {!! Form::number('y_coordinate', $city->y_coordinate, ['placeholder' => __('Y coordinate'),'class' => 'form-control', 'step' => '0.00000000000001']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection