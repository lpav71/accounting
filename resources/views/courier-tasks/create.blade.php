@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Courier tasks create') }}</h2>
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
    {!! Form::open(['route' => 'courier-tasks.store', 'method' => 'post']) !!}
    <div class="form-group col-xs-12 col-sm-2" id="deliveryDate">
        {!! Form::label('date', __('Date:')) !!}
        @php
            use Illuminate\Support\Carbon;
        @endphp
        {!! Form::text('date', Carbon::today()->format('d-m-Y'), ['class' => 'form-control date', 'autocomplete' => 'off', 'required' => true]) !!}
    </div>
    <div class="col-xs-12 col-sm-6">
        {!! Form::label('address', __('Address')) !!}
        {!! Form::text('address', null, ['class' => 'form-control', 'required' => true, 'autocomplete' => 'off']) !!}
    </div>
    <div class="col-xs-12 col-sm-6">
        {!! Form::label('city', __('City')) !!}
        {!! Form::text('city', __('Moscow'), ['class' => 'form-control', 'required' => true, 'autocomplete' => 'off']) !!}
    </div>
    <div class="col-xs-12 col-sm-6">
        {!! Form::label('city_id', __('City')) !!}
        {!! Form::select('city_id', \App\City::pluck('name', 'id'), ['class' => 'form-control selectpicker']) !!}
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            {!! Form::label('comment', __('Comment')) !!}
            {!! Form::textarea('comment', null, [ 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-4">
        <div class="form-group row" id="deliveryTime">
            <div class="col-6">
                {!! Form::label('start_time', __('Time from:')) !!}
                {!! Form::text('start_time', null, ['class' => 'form-control time start']) !!}
            </div>
            <div class="col-6">
                {!! Form::label('end_time', __('Time to:')) !!}
                {!! Form::text('end_time', null, ['class' => 'form-control time end']) !!}
            </div>
        </div>
    </div>
    <div class="col-12 text-left mb-5 mt-5">
        {!! Form::button(__('Submit'), ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
    </div>
    {!! Form::close() !!}
@endsection