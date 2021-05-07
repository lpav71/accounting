@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Adword campaign ids') }}</h2>
            </div>
            <div class="pull-right">
                @can('campaign-id-create')
                    <a class="btn btn-sm btn-success" href="{{ route('campaign-ids.create') }}"> {{ __('Add new id') }}</a>
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
            <th>{{ __('Campaign id') }}</th>
            <th>{{ __('utm_campaign') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($campaignIds as $key => $campaignId)
            <tr>
                <td>{{ $campaignId->id }}</td>
                <td>{{ $campaignId->campaign_id }}</td>
                <td>{{ $campaignId->utm_campaign->name }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('campaign-id-edit')
                                <a class="dropdown-item" href="{{ route('campaign-ids.edit',$campaignId->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('campaign-id-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['campaign-ids.destroy', $campaignId->id],'style'=>'display:inline']) !!}
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
    {!! $campaignIds->render() !!}
@endsection