@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create new store auto transfer setting') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('store-autotransfer-settings.index') }}"> {{ __('Back') }}</a>
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



    {!! Form::open(['route' => 'store-autotransfer-settings.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('max_amount', __('Max amount')) !!}
                {!! Form::text('max_amount', null, ['placeholder' => __('Max amount'),'class' => 'form-control']) !!}
            </div>
        </div>
        <!---->
        <div class="col-12 col-sm-4">
            <div class="form-group">
                {!! Form::label('max_day', __('Max day')) !!}
                {!! Form::text('max_day', null, ['placeholder' => __('Max day'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="form-group">
                {!! Form::label('min_day', __('Min day')) !!}
                {!! Form::text('min_day', null, ['placeholder' => __('Min day'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="form-group">
                {!! Form::label('latest_sales_days', __('The number of days for which sales are counted')) !!}
                {!! Form::text('latest_sales_days', null, ['placeholder' => __('The number of days for which sales are counted'),'class' => 'form-control']) !!}
            </div>
        </div>
        <!---->
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('main_store_id', __('Main store')) !!}
                {!! Form::select('main_store_id', $stores, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="form-group">
                {!! Form::label('reserve_store_id', __('Reserve store')) !!}
                {!! Form::select('reserve_store_id', $stores, null, ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <h3>{{__('Minimum products on main store')}}</h3>
        </div>
        <div class="col-12 row">
            @foreach($settings['min'] as $key => $setting)
                @if($key % 11 == 1)
                    <div class="col-xs-12 col-sm-3">
                        @endif
                        <div class="form-group row">
                            {!! Form::label("settings[min][$key]", $setting['name'], ['class' => 'col-sm-6 col-form-label text-right']) !!}
                            {!! Form::text("settings[min][$key]", $setting['value'], ['class' => 'form-control col-sm-4']) !!}
                        </div>
                        @if($key % 11 == 0 || $key == array_key_last($settings))
                    </div>
                @endif
            @endforeach
        </div>
        <div class="col-12">
            <h3>{{__('Transaction limit')}}</h3>
        </div>
        <div class="col-12 row">
            @foreach($settings['limit'] as $key => $setting)
                @if($key % 11 == 1)
                    <div class="col-xs-12 col-sm-3">
                        @endif
                        <div class="form-group row">
                            {!! Form::label("settings[limit][$key]", $setting['name'], ['class' => 'col-sm-6 col-form-label text-right']) !!}
                            {!! Form::text("settings[limit][$key]", $setting['value'], ['class' => 'form-control col-sm-4']) !!}
                        </div>
                        @if($key % 11 == 0 || $key == array_key_last($settings))
                    </div>
                @endif
            @endforeach
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection