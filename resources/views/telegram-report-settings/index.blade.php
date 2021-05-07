@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Telegram report management') }}</h2>
            </div>
            <div class="pull-right">
                @can('telegram-report-settings-create')
                    <a class="btn btn-sm btn-success" href="{{ route('telegram-report-settings.create') }}"> {{ __('Add new account') }}</a>
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
            <th>{{ __('Name') }}</th>
            <th>{{ __('Time') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($settings as $key => $attribute)
            <tr>
                <td>{{ $attribute->name }}</td>
                <td>{{ $attribute->time }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('telegram-report-settings.show',$attribute->id) }}">{{ __('Show') }}</a>
                            @can('telegram-report-settings-edit')
                                <a class="dropdown-item" href="{{ route('telegram-report-settings.edit',$attribute->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('telegram-report-settings-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['telegram-report-settings.destroy', $attribute->id],'style'=>'display:inline']) !!}
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
          * @var $attributes \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $settings->render() !!}
@endsection