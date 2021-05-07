@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Создать новое правило</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('substitute.index') }}"> {{ __('Back') }}</a>
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



    {!! Form::open(['route' => 'substitute.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('find', 'Что искать') !!}
                {!! Form::text('find', null, ['placeholder' => 'Что искать','class' => 'form-control']) !!}
                <small id="findHelp" class="form-text text-muted">Только слова целиком</small>
            </div>
            <div class="form-group">
                {!! Form::label('replace', 'Чем заменить') !!}
                {!! Form::text('replace', null, ['placeholder' => 'Чем заменить','class' => 'form-control']) !!}
                <small id="findHelp" class="form-text text-muted">Только слова целиком</small>
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection