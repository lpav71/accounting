@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Main store balancer') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('transfer-iterations.index') }}"> {{ __('Back') }}</a>
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
    <div class="row">
        <div class="col-xs-12 col-sm-4">
            <div class="form-group">
                <strong>{{ __('From store') }}</strong>
                {{ $transferIteration->storeFrom->name }}
            </div>
        </div>
        <div class="col-xs-12 col-sm-4">
            <div class="form-group">
                <strong>{{ __('To store') }}</strong>
                {{ $transferIteration->storeTo->name }}
            </div>
        </div>
        {{ Form::open(['url' => route('transfer-iterations.process')]) }}
        <input type="hidden" name="transfer_iteration_id" value="{{$transferIteration->id}}">
        <input type="hidden" name="store_id_from" value="{{$transferIteration->store_id_from}}">
        <input type="hidden" name="store_id_to" value="{{$transferIteration->store_id_to}}">
        <table class="col-xs-12 col-sm-15 table">
            <thead>
            <tr>
                <th scope="col">{{__('Id')}}</th>
                <th scope="col">{{__('Reference')}}</th>
                <th scope="col">{{__('Current')}}<br>({{$transferIteration->storeFrom->name}})</th>
                <th scope="col">{{__('Transfer')}}</th>
                <th scope="col">{{__('After transfer')}}<br>({{$transferIteration->storeFrom->name}})</th>
                <th scope="col">{{__('Current')}}<br>({{$transferIteration->storeTo->name}})</th>
                <th scope="col">{{__('After transfer')}}<br>({{$transferIteration->storeTo->name}})</th>
            </tr>
            </thead>
            <tbody>
            @foreach($transferProducts as $key => $transferProduct)
                <tr>
                    <th scope="col">{{$key}}</th>
                    <th scope="col">{{$transferProduct['reference']}}</th>
                    <th scope="col">{{$transferProduct['current']}}</th>
                    <th scope="col"><input name="product[{{$key}}]}}" type="text" class="form-control" value="{{$transferProduct['transfer']}}"></th>
                    <th scope="col">{{$transferProduct['current'] - $transferProduct['transfer']}}</th>
                    <th scope="col">{{$transferProduct['currentReserve']}}</th>
                    <th scope="col">{{$transferProduct['currentReserve'] + $transferProduct['transfer']}}</th>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Process iteration') }}</button>
        </div>
        {{ Form::close() }}
    </div>
@endsection