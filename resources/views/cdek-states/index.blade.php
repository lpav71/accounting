@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('CDEK states management') }}</h2>
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
    <table class="table table-light table-bordered table-responsive-xs">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('State code') }}</th>
            <th>{{ __('Task existing') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($states as $key => $state)
            <tr>
                <td>{{ $state->id }}</td>
                <td>{{ $state->name }}</td>
                <td>{{ $state->state_code }}</td>
                <td>{!! Form::checkbox( '', 1, $state->need_task , array('class' => 'form-control','disabled')) !!}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('cdek-states.show',$state->id) }}">{{ __('Show') }}</a>
                            @can('cdek-states-edit')
                                <a class="dropdown-item" href="{{ route('cdek-states.edit',$state->id) }}">{{ __('Edit') }}</a>
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $categories \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $states->render() !!}
@endsection
