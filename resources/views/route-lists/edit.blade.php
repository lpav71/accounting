@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('Edit Route List') }} {{ $courier->name }}</h4>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('route-lists.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
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
    {!! Form::model($routeList, ['route' => ['route-lists.update', $routeList],'method'=>'PATCH']) !!}
    {!! Form::hidden('updated_at_version', $routeList->updated_at->toDateTimeString()) !!}
    {!! Form::hidden('courier_id', $courier->id, ['id' => 'courier_id']) !!}
    {!! Form::close() !!}
    @if($routeList->routePoints->isNotEmpty())
        {!! Form::open(['method'=>'GET']) !!}
        <a class="btn btn-success" id="btn-pay" style="color: white">{{__('Finance input')}}</a>
        <table class="table table-sm table-bordered small mt-3 hover-table table-responsive">
            <thead class="bg-info">
            <tr>
                <th class="text-nowrap">{{ __('Type') }}</th>
                <th class="text-nowrap">{{ __('P-t Id') }}</th>
                <th class="text-nowrap">{{ __('P-t State') }}</th>
                <th class="text-nowrap">{{ __('Time') }}</th>
                <th class="text-nowrap">{{ __('Address') }}</th>
                <th class="text-nowrap">{{ __('P-t Object Id') }}</th>
                <th class="text-nowrap">{{ __('Id #1') }}</th>
                <th class="text-nowrap">{{ __('P-t Object State') }}</th>
                <th class="text-nowrap">{{ __('Estimated delivery date:') }}</th>
                <th class="text-nowrap">{{ __('Finance') }}</th>
            </tr>
            </thead>
            <tbody>
            <tr id="searchOrders">
                <td class="p-0"
                    area-label="{{ __('State') }}">{{ Form::select('type', $types , Request::input('type'), ['class' => 'form-control form-control-sm selectpicker']) }}</td>
                <td></td>
                <td class="p-0"
                    area-label="{{ __('State') }}">{{ Form::select('pointState', $pointStates , Request::input('pointState'), ['class' => 'form-control form-control-sm selectpicker']) }}</td>
                <td></td>
                <td class="p-0"
                    area-label="{{ __('Address') }}">{{ Form::text('address', Request::input('address'), ['class' => 'form-control form-control-sm search-input']) }}</td>
                <td></td>
                <td></td>
                <td class="p-0" area-label="{{ __('State') }}">
                    {{ Form::select('states[]', $pointObjectStates , Request::input('states'), ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) }}
                </td>
                <td class="p-0"
                    area-label="{{ __('Date') }}">{!! Form::text('date', Request::input('date'), ['class' => 'form-control form-control-sm date search-input text-center', 'size' => 3]) !!}</td>
                <td></td>
                <td class="text-center p-1">
                    {{ Form::button('<i class="fa fa-search"></i>', ['class' => 'btn btn-sm', 'type' => 'submit']) }}
                    <a class="btn btn-sm" href="{{ route('route-lists.edit', $routeList) }}"><i class="fa fa-close"></i></a>
                </td>
            </tr>

            @foreach($paginatedItems as $routePoint)
                    @include('route-lists._partials.route-point.row', ['routePoint' => $routePoint])

                @endforeach
            </tbody>
        </table>
        {!! $paginatedItems->appends(['type' => Request::input('type'), 'pointState' => Request::input('pointState'), 'address' => Request::input('address'), 'states' => Request::input('states'), 'date' => Request::input('date')])->render() !!}
{!! Form::close() !!}
    @endif
@endsection