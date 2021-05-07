@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Phone calls') }}</h2>
            </div>
        </div>
    </div>
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning">
            <p>{{ $message }}</p>
        </div>
    @endif
    <div>
        {{Form::open(['method' => 'get'])}}
        <div class="form-group row clearfix col-lg-12">
            <div class="row">
                <div class="input-group-prepend">
                    {!! Form::label('date_from', __('From'), ['class' => 'form-control m-0']) !!}
                </div>
                <div class="input-group-append">
                    {!! Form::date('date_from', Request::input('date_from'), ['class' => 'form-control', 'autocomplete' => 'off']) !!}
                </div>
            </div>
            <div class="row ml-5">
                <div class="input-group-prepend">
                    {!! Form::label('date_to', __('To'), ['class' => 'form-control m-0']) !!}
                </div>
                <div class="input-group-append">
                    {!! Form::date('date_to', Request::input('date_to'), ['class' => 'form-control', 'autocomplete' => 'off']) !!}
                </div>
            </div>
            <button type="submit" class="btn btn-info ml-5">{{ __('Send') }}</button>
        </div>
       
        {{ Form::close() }}
        <div>
            <table class="w-50 table table-striped">
                <tr>
                    <td>{{__('Abonent')}}</td>
                    <td>{{__('Length (min)')}}</td>
                    <td>{{__('Count')}}</td>
                </tr>
                @foreach ($abonentsStats as $userId => $abonentsStat)
                    <tr>
                        <td>{{$userId}}</td>
                        <td>{{$abonentsStat['duration']}}</td>
                        <td>{{$abonentsStat['count']}}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection