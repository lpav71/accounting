@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Rules for accessing orders') }}</h2>
            </div>
            <div class="pull-right">
                @can('substitutes-create')
                    <a class="btn btn-sm btn-success" href="{{ route('rule-order-permission.create') }}"> Создать новое правило</a>
                @endcan
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
            <th>{{ __('Name rule') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rops as $key => $rop)
            <tr>
                <td>{{ $rop->id }}</td>
                <td>{{ $rop->name }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('rule-order-permission.show',$rop->id) }}">{{ __('Show') }}</a>
                                <a class="dropdown-item" href="{{ route('rule-order-permission.edit',$rop->id) }}">{{ __('Edit') }}</a>
                                {!! Form::open(['method' => 'DELETE','route' => ['rule-order-permission.destroy', $rop->id],'style'=>'display:inline']) !!}
                                {!! Form::button(__('Delete'), ['class' => 'dropdown-item', 'type' => 'submit']) !!}
                                {!! Form::close() !!}
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {!! $rops->render() !!}
@endsection
