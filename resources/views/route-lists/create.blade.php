@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create New Route List') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('route-lists.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::open(['route' => 'route-lists.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-12 row">
            <div class="form-group col-xs-12 col-sm-5">
                {!! Form::label('courier_id', __('Courier:')) !!}
                {!! Form::select('courier_id', $couriers, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection