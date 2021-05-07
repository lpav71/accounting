@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Create new reference company template') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('references-companies-templates.index') }}"> {{ __('Back') }}</a>
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



    {!! Form::open(['route' => 'references-companies-templates.store','method'=>'POST']) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name:')) !!}
                {!! Form::text('name', null, ['placeholder' => __('Name'),'class' => 'form-control']) !!}
            </div>
            <div class="form-group ">
                {!! Form::label('ad_type', __('Ad company type').':') !!}
                {!! Form::text('ad_type', null, array('class' => 'form-control')) !!}
            </div>
            <p>{{__('Replacements')}}</p>
            <div class="form-group ">
                {!! Form::label('phrase', __('Phrase (with minus words)').':') !!}
                {!! Form::text('phrase', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group ">
                {!! Form::label('header_1', __('Header 1 (max 35 letters with spaces)').':') !!}
                {!! Form::text('header_1', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('header_2', __('Header 2 (max 30 letters with spaces)').':') !!}
                {!! Form::text('header_2', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('text', __('Text (81 letters max)').':') !!}
                {!! Form::text('text', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('link_text', __('Text of the link (max 20 letters and №, /, %, #)').':') !!}
                {!! Form::text('link_text', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('region', __('Region').':') !!}
                {!! Form::text('region', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('bet', __('Bet').':') !!}
                {!! Form::text('bet', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('quick_link', __('Quick link headers (separator is ||)').':') !!}
                {!! Form::text('quick_link', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('quick_link_descr', __('Sitelink descriptions (separator is ||)').':') !!}
                {!! Form::text('quick_link_descr', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('quick_link_addr', __('Quick link addreses (separator is ||)').':') !!}
                {!! Form::textarea('quick_link_addr', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('details', __('Details').':') !!}
                {!! Form::text('details', null, array('class' => 'form-control')) !!}
            </div>
            <div class="form-group">
                {!! Form::label('link', __('Channel Yandex references companies endpoint').':') !!}
                {!! Form::text('link', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
    </div>
    {!! Form::close() !!}


@endsection