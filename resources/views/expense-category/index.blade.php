@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Expense categories') }}</h2>
            </div>
        </div>
    </div>
    @if ($message = Session::get('success'))
        <div class="alert alert-success mt-1">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning mt-1">
            <p>{{ $message }}</p>
        </div>
    @endif
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
    <div class="pull-right">
        <a class="btn btn-sm btn-success"
           href="{{ route('expense-category.create') }}"> {{ __('Create expense category') }}</a>
    </div>
    <table class="table table-light table-bordered">
        <thead class="bg-info">
        <tr>
            <th class="text-nowrap">{{ __('Name') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($expenseCategories as $category)
            <tr>
                <td><a href="{{ route('expense-category.edit', $category->id) }}"class="text-dark d-block text-center">{{ $category->name }}</a> </td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('expense-category.edit', $category->id) }}">{{ __('Edit') }}</a>
                            @can('expense-setting')
                                {{ Form::open(['route' => ['expense-category.destroy', $category->id], 'method' => 'delete']) }}
                                {!! Form::button(__('Delete'), ['type' => 'submit', 'class' => 'dropdown-item']) !!}
                                {{ Form::close() }}
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection