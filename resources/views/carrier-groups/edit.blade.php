@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Carrier group edit') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('carrier-group.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($carrier_group, ['method' => 'PATCH','route' => ['carrier-group.update', $carrier_group->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name').':') !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('user_id[]', __('Users:')) !!}
                {!! Form::select('user_id[]', \App\User::all()->pluck('name', 'id'), $carrier_group->users()->pluck('user_id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('carrier_id[]', __('Carriers')) !!}
                {!! Form::select('carrier_id[]', \App\Carrier::all()->pluck('name', 'id'), \App\Carrier::where(['carrier_group_id' => $carrier_group->id])->pluck('id'), ['multiple' => true, 'class' => 'form-control selectpicker']) !!}
            </div>
            <div class="col-12 text-left">
                <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
@endsection