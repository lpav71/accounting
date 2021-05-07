@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit attribute') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('campaign-ids.index') }}"> {{ __('Back') }}</a>
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
    {!! Form::model($campaignId, ['method' => 'PATCH','route' => ['campaign-ids.update', $campaignId->id]]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('campaign_id', __('Campaign id')) !!}
                {!! Form::text('campaign_id', null, ['placeholder' => __('Campaign id'),'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('utm_campaign_id', __('Campaign')) !!}
                {!! Form::select('utm_campaign_id', \App\UtmCampaign::orderBy('name')->pluck('name','id'), $campaignId->utm_campaign_id , ['class' => 'form-control selectpicker']) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}
@endsection