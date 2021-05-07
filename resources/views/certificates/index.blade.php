@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Certificates management') }}</h2>
            </div>
            <div class="pull-right">
                @can('certificate-create')
                    <a class="btn btn-sm btn-success" href="{{ route('certificates.create') }}"> {{ __('Create new certificate') }}</a>
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
            <th>{{__('Number')}}</th>
            <th>{{__('Balance')}}</th>
            <th>{{__('Channel') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($certificates as $key => $certificate)
            <tr>
                <td>{{ $certificate->id }}</td>
                <td>{{ $certificate->number }}</td>
                <td>{{ $certificate->getBalance() }}</td>
                <td>{{ $certificate->orderDetail ? $certificate->orderDetail->order->channel->name : '' }}</td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('certificate-edit')
                                <a class="dropdown-item" href="{{ route('certificates.edit',$certificate->id) }}">{{ __('Edit') }}</a>
                            @endcan
                            @can('certificate-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['certificates.destroy', $certificate->id],'style'=>'display:inline']) !!}
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
          * @var $certificates \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $certificates->render() !!}
@endsection