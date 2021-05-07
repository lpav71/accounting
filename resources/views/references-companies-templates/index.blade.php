@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('References companies templates') }}</h2>
            </div>
            <div class="pull-right">
                @can('references-companies-templates-create')
                    <a class="btn btn-sm btn-success" href="{{ route('references-companies-templates.create') }}"> {{ __('Create new template') }}</a>
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
            <th>{{ __('Name') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($templates as $key => $template)
            <tr>
                <td>{{ $template->id }}</td>
                <td>{{ $template->name }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('references-companies-templates.show',$template->id) }}">{{ __('Show') }}</a>
                            @can('references-companies-templates-edit')
                                <a class="dropdown-item" href="{{ route('references-companies-templates.edit',$template->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('references-companies-templates-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['references-companies-templates.destroy', $template->id],'style'=>'display:inline']) !!}
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
          * @var $templates \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $templates->render() !!}
@endsection