@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Replacement rules') }}</h2>
            </div>
            <div class="pull-right">
                @can('substitutes-create')
                    <a class="btn btn-sm btn-success" href="{{ route('substitute.create') }}"> Создать новое правило</a>
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
            <th>{{ __('Find') }}</th>
            <th>{{ __('Replace') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($substitutes as $key => $substitute)
            <tr>
                <td>{{ $substitute->id }}</td>
                <td>{{ $substitute->find }}</td>
                <td>{{ $substitute->replace }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('substitute.show',$substitute->id) }}">{{ __('Show') }}</a>
                            @can('substitutes-edit')
                                <a class="dropdown-item" href="{{ route('substitute.edit',$substitute->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('substitutes-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['substitute.destroy', $substitute->id],'style'=>'display:inline']) !!}
                                {!! Form::button(__('Delete'), ['class' => 'dropdown-item', 'type' => 'submit']) !!}
                                {!! Form::close() !!}
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
          * @var $substitutes \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $substitutes->render() !!}
@endsection