@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Main store balancer') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('store-autotransfer-settings.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-4">
            <div class="form-group">
                <strong>{{ __('Name:') }}</strong>
                {{ $setting->name }}
            </div>
        </div>
        <div class="col-xs-12 col-sm-4">
            <div class="form-group">
                <strong>{{ __('Main store') }}</strong>
                {{ $mainStore->name }}
            </div>
        </div>
        <div class="col-xs-12 col-sm-4">
            <div class="form-group">
                <strong>{{ __('Reserve store') }}</strong>
                {{ $reserveStore->name }}
            </div>
        </div>
        {{ Form::open(['url' => route('transfer-iterations.store')]) }}
        <input type="hidden" name="store_id_from" value="{{$mainStore->id}}">
        <input type="hidden" name="store_id_to" value="{{$reserveStore->id}}">
        <table class="col-xs-12 col-sm-12 table">
            <thead>
            <tr>
                <th scope="col" colspan="9"><strong>{{ __('Amount in transfer to reserved') }}</strong>
                    {{ $amount }}</th>
            </tr>
            <tr>
                <th scope="col">{{__('Id')}}</th>
                <th scope="col">{{__('Reference')}}</th>
                <th scope="col">{{__('Average sales')}}</th>
                <th scope="col">{{__('Min quantity')}}</th>
                <th scope="col">{{__('Current')}}<br>({{$mainStore->name}})</th>
                <th scope="col">{{__('Transfer')}}</th>
                <th scope="col">{{__('After transfer')}}<br>({{$mainStore->name}})</th>
                <th scope="col">{{__('Current on reserved')}}<br>({{$reserveStore->name}})</th>
                <th scope="col">{{__('On reserved after transfer')}}<br>({{$reserveStore->name}})</th>
            </tr>
            </thead>
            <tbody>
            @foreach($transferProducts as $key => $transferProduct)
                <tr>
                    <th scope="col">{{$key}}</th>
                    <th scope="col">{{$transferProduct['reference']}}</th>
                    <th scope="col">{{$transferProduct['sales']}}</th>
                    <th scope="col">{{$transferProduct['settingsMin']}}</th>
                    <th scope="col">{{$transferProduct['current']}}</th>
                    <th scope="col"><input name="transfer[{{$key}}]}}" type="text" readonly class="form-control-plaintext" value="{{$transferProduct['transfer']}}"></th>
                    <th scope="col">{{$transferProduct['current'] - $transferProduct['transfer']}}</th>
                    <th scope="col">{{$transferProduct['currentReserve']}}</th>
                    <th scope="col">{{$transferProduct['currentReserve'] + $transferProduct['transfer']}}</th>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Create iteration') }}</button>
        </div>
        {{ Form::close() }}
    </div>
@endsection