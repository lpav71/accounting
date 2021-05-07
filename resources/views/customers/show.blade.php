@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Show Customer') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('customers.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('First Name:') }}</strong>
                {{ $customer->first_name }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('Last Name:') }}</strong>
                {{ $customer->last_name }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('Phone:') }}</strong>
                {{ $customer->phone }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('E-mail:') }}</strong>
                {{ $customer->email }}
            </div>
        </div>
        <table class="table table-light table-bordered table-responsive-sm">
            <thead class="thead-light">
            <tr>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Time') }}</th>
                <th>{{ __('Record') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($customer->calls() as $key => $call)
                <tr>
                    <td>{{ $call->isOutgoing() ? __('Outgoing') : __('Incoming') }}</td>
                    <td>{{ $call->created_at->format('d-m-Y H:i') }}</td>
                    <td>@if($call->recordUrl)<audio controls="controls">
                            <source src="{{ $call->recordUrl }}" type="audio/mp3">
                        </audio>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection