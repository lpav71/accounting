@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Show Product') }}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('products.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('Name:') }}</strong>
                {{ $product->name }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('Reference:') }}</strong>
                {{ $product->reference }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('EAN:') }}</strong>
                {{ $product->ean }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <strong>{{ __('Manufacturer:') }}</strong>
                {{ $product->manufacturer->name }}
            </div>
        </div>
        @if ($product->isComposite())
            <div class="col-12">
                <div class="form-group">
                    <strong>{{ __('Composite product') }}</strong>
                </div>
            </div>
            <table class="table table-light table-bordered table-responsive-sm">
                <thead class="thead-light">
                <tr>
                    <th>{{ __('Id') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Reference') }}</th>
                    <th>{{ __('EAN') }}</th>
                    <th>{{ __('Manufacturer') }}</th>
                    <th>{{ __('Composite') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($product->products as $key => $productPart)
                    <tr>
                        <td>{{ $productPart->id }}</td>
                        <td>
                            {{ $productPart->name }}
                            @if ($productPart->isComposite())
                                <div class="clear">
                                    @foreach($productPart->products()->pluck('name') as $v)
                                        <label class="badge badge-success">{{ $v }}</label>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>{{ $productPart->reference }}</td>
                        <td>{{ $productPart->ean }}</td>
                        <td>{{ $productPart->manufacturer->name }}</td>
                        <td class="text-center"><i class="fa @if ($productPart->isComposite()) fa-check @else fa-close @endif"></i></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection