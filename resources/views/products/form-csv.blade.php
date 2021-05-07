@extends('layouts.app')

@section('content')
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
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Import from CSV') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('products.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    {{ Form::open(['url' => route('products.csv.post'),'files' => true, 'class'=>'']) }}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                    {!! Form::file('csv_file',['class'=>'form-control-file','required','id'=>'csv_file']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <div class="custom-control custom-switch">
                    {{ Form::checkbox('update_products', 1 ,0,['class'=>'custom-control-input','id'=>'update_products']) }}
                    {!! Form::label('update_products', __('Update existing products'), ['class' => 'custom-control-label']) !!}
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <div class="custom-control custom-switch">
                    {{ Form::checkbox('add_products', 1 ,0,['class'=>'custom-control-input','id'=>'add_products']) }}
                    {!! Form::label('add_products', __('Add products'), ['class' => 'custom-control-label']) !!}
                </div>
            </div>
        </div>
        
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
    <br>
    <br>
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Update availability') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('products.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    {{ Form::open(['url' => route('products.csv.post.availability'),'files' => true, 'class'=>'']) }}

    <div class="row">
        <div class="col-12">
            <div class="form-group">
                    {!! Form::file('csv_file',['class'=>'form-control-file','required','id'=>'csv_file']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('channels[]', __('Channels').':') !!}
                {!! Form::select('channels[]', \App\Channel::whereContentControl()->orderBy('name')->pluck('name','id'), null , ['title'=>__('Choose channels'), 'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('manufacturers[]', __('Manufacturers').':') !!}
                {!! Form::select('manufacturers[]', \App\Manufacturer::orderBy('name')->pluck('name','id'), null , ['title'=>__('All manufacturers'),'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
    <br>
    <br>
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Block products') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('products.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    {{ Form::open(['url' => route('products.csv.post.banned'),'files' => true, 'class'=>'']) }}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                    {!! Form::file('csv_file',['class'=>'form-control-file','required','id'=>'csv_file']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('channels[]', __('Channels').':') !!}
                {!! Form::select('channels[]', \App\Channel::whereContentControl()->orderBy('name')->pluck('name','id'), null , ['title'=>__('Choose channels'), 'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('manufacturers[]', __('Manufacturers').':') !!}
                {!! Form::select('manufacturers[]', \App\Manufacturer::orderBy('name')->pluck('name','id'), null , ['title'=>__('All manufacturers'),'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection