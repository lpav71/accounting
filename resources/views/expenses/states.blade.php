@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Setting states for expenses') }}</h2>
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
    <div class="col-12">
        {!! Form::open(['route' => 'expense-settings.states.store','method'=>'POST', 'class' => 'form-group']) !!}
        <div class="form-group mt-4">
            {!! Form::label('successful_states[]', __('Summarize prices for items in status').':') !!}
            {!! Form::select('successful_states[]', $orderDetailStates, $successful_states, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        <div class="form-group">
            {!! Form::label('minimal_states[]', __('If not, then consider the minimum prices for positions with a guarantee in statuses').':') !!}
            {!! Form::select('minimal_states[]', $orderDetailStates, $minimal_states, ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
        </div>
        {!! Form::submit(__('Save'), ['class' => 'btn btn-success', 'name' => 'submit']) !!}
        {!! Form::close() !!}
    </div>
    @endsection
