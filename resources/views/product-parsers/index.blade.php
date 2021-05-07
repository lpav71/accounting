@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Product Parsers Management') }}</h4>
            </div>
            <div class="pull-right">
                <a class="btn btn-sm btn-success" href="{{ route('product-parsers.create') }}"> {{ __('Create New Product Parser') }}</a>
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
    <table class="table table-light table--responsive table-sm table-bordered table-striped small order-table">
        <thead class="thead-light">
        <tr>
            <th class="align-middle">{{ __('Id') }}</th>
            <th class="align-middle">{{ __('Name') }}</th>
            <th class="align-middle">{{ __('Link') }}</th>
            <th class="align-middle">{{ __('Active') }}</th>
            <th class="align-middle"></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($parsers as $key => $parser)
            <tr>
                <td><a href="{{ route('product-parsers.edit',$parser->id) }}">{{ $parser->id }}</a></td>
                <td>{{ $parser->name }}</td>
                <td>{{ $parser->link }}</td>
                <td class="text-center"><i class="fa @if ($parser->is_active) fa-check @else fa-close @endif"></i></td>
                <td><a class="btn btn-primary" href="{{ route('product-parsers.show', $parser->id) }}">{{ __("Use") }}</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $parsers \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $parsers->render() !!}
@endsection