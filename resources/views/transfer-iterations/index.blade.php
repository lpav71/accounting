@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Transfer iterations') }}</h2>
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
    <table class="table table-light table-bordered table-responsive-xs">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('From store') }}</th>
            <th>{{ __('To store') }}</th>
            <th>{{ __('State') }}</th>
            <th>{{ __('Transfered count') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($iterations as $key => $iteration)
            <tr>
                <td>{{  $iteration->id }}</td>
                <td>{{  $iteration->storeFrom->name}}</td>
                <td>{{  $iteration->storeTo->name}}</td>
                <td>@if($iteration->is_completed) {{__('Processed')}} @else {{__('Not processed')}} @endif</td>
                <td>@if($iteration->is_completed){{  $iteration->transfered_count}}@endif</td>
                <td>
                    @can('transfer-iterations-process')
                        @if(!$iteration->is_completed)
                        <a class=""
                           href="{{ route('transfer-iterations.show',$iteration->id) }}">{{ __('Process') }}</a>
                        @endif
                    @endcan
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $iterations \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $iterations->render() !!}
@endsection