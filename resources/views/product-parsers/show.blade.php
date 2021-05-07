@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Use parser') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('product-parsers.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>{{ __('Name') }}</strong>
                {{ $parser->name }}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>{{ __('Link:') }}</strong>
                    {{ $parser->link }}
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
    {{ Form::open(['url' => route('parser.csv.prices',$parser->id),'files' => true, 'class'=>'']) }}
    <div class="row">
        <div class="col-md-12">
            
            <div class="">
                    <h3>{{__('CSV prices by references')}}</h3>
                <div class="form-group">
                        {!! Form::file('csv_file',['class'=>'form-control-file','required','id'=>'csv_file']) !!}
                </div>
                <div class="text-left">
                        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                </div>
            </div>
            
            <div>
                @if ($parser->price_parser_files->where('is_ready',0)->count())
                <div class="text-left text-warning">
                    <h4 class="mt-2 mb-0">{{__('In process')}}</h4>
                    @foreach ($parser->price_parser_files->where('is_ready',0) as $file)
                        <p class="mb-0"><a href="{{$file->url}}">{{ $file->name }}</a></p>
                    @endforeach
                </div>
                @endif
                @if ($parser->price_parser_files->where('is_ready',1)->count())
                <div class="text-left text-warning">
                    <h4 class="mt-2 mb-0">{{__('Ready')}}</h4>
                    @foreach ($parser->price_parser_files->where('is_ready',1) as $file)
                        <p class="mb-0"><a href="{{$file->url}}">{{ $file->name }}</a></p>
                    @endforeach
                </div>
                @endif
            </div>           

            <hr>
        </div>
    </div>
    {!! Form::close() !!}        
@endsection