@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Expense settings') }}</h2>
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
           href="{{ route('expense-settings.create') }}"> {{ __('Create extense setting') }}</a>
    </div>
    <table class="table table-light table-bordered">
        <thead class="bg-info">
        <tr>
            <th class="text-nowrap">{{ __('Name') }}</th>
            <th class="text-nowrap">{{ __('Extense summ') }}</th>
            <th class="text-nowrap">{{ __('Utm Campaigns') }}</th>
            <th class="text-nowrap">{{ __('Category') }}</th>
            <th class="text-nowrap">{{ __('Brands') }}</th>
            <th class="text-nowrap">{{ __('Carriers') }}</th>
            <th class="text-nowrap">{{__('Order States')}}</th>
            <th class="text-nowrap">{{__('Channels')}}</th>
            <th class="text-nowrap">{{__('Expense category')}}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($settings as $setting)
            <tr>
                <td><a href="{{ route('expense-settings.edit', $setting->id) }}"class="text-dark d-block text-center">{{ $setting->name }}</a> </td>
                <td>{{ $setting->summ }}</td>
                <td>@if($setting->utmCampaign){{ $setting->utmCampaign->name }} @endif</td>
                <td>@if($setting->category){{ $setting->category->name }}@endif</td>
                <td>
                    @if(count($setting->manufacturters))
                        {{__('See inside')}}
                    @endif
                </td>
                <td>
                    @if(count($setting->carriers))
                        {{__('See inside')}}
                    @endif
                </td>
                <td>
                    @if(count($setting->orderStates))
                        {{__('See inside')}}
                    @endif
                </td>
                <td>
                    @if(count($setting->channels))
                        {{__('See inside')}}
                    @endif
                </td>
                <td>
                    @if($setting->expenseCategory)
                        {{$setting->expenseCategory->name}}
                    @endif
                </td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('expense-settings.edit', $setting->id) }}">{{ __('Edit') }}</a>
                            <a class="dropdown-item" href="{{ route('expense-settings.copy', $setting->id) }}">{{ __('Copy') }}</a>
                            @can('expense-setting')
                                {{ Form::open(['route' => ['expense-settings.destroy', $setting->id], 'method' => 'delete']) }}
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