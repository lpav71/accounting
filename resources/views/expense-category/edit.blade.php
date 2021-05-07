@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit expense category') }}</h2>
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
    {!! Form::open(['route' => ['expense-category.update', $expenseCategory], 'method' => 'PUT']) !!}
    <div class="col-12 mt-3">
        {!! Form::label('name', __('Category name')) !!}
        {!! Form::textarea('name', $expenseCategory->name, ['class' => 'form-control', 'rows' => 2, 'required' => true]) !!}
    </div>
    <div class="col-12 text-left mb-5 mt-5">
        {!! Form::button(__('Submit'), ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
    </div>
    {!! Form::close() !!}
@endsection