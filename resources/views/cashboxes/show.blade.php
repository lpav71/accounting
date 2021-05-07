@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Show Cashbox') }}</h2>
            </div>
            @can('cashbox-list')
                <div class="pull-right">
                    <a class="btn btn-primary" href="{{ route('cashboxes.index') }}"> {{ __('Back') }}</a>
                </div>
            @endcan
        </div>
    </div>
    <div class="row title-block">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>{{ __('Name:') }}</strong>
                {{ $cashbox->name }}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>{{ __('Users:') }}</strong>
                @if(!empty($cashbox->users))
                    @foreach($cashbox->users as $v)
                        <label class="badge badge-success">{{ $v->name }}</label>
                    @endforeach
                @endif
            </div>
        </div>
        @foreach ($cashbox->operableIds() as $operableId)
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    @php
                    $currency = \App\Currency::find($operableId);
                    @endphp
                    {{ __('Balance in Currency') }} <strong>{{ !is_null($currency) ? $currency->name : ''}}:</strong>
                            {{ $cashbox->getCurrentQuantity($operableId) }}
                </div>
            </div>
            @endforeach
        @can('cashbox-operation')
            @if($cashbox->users()->find(Auth::user()->id))
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="pull-right">
                        <a class="btn btn-sm btn-success"
                           href="{{ route('cashbox-operations.create', ['cashbox' => $cashbox]) }}"> {{ __('Create New Operation') }}</a>
                    </div>
                    @if(in_array(\Auth::id(), $cashbox->usersWithTransferRights()->pluck('user_id')->toArray()))
                    <div class="pull-right">
                        <a class="btn btn-sm btn-info mr-3"
                           href="{{ route('cashbox.transfer', ['cashbox' => $cashbox]) }}"> {{ __('Transfer currency between cashboxes') }}</a>
                    </div>
                    @endif
                </div>
            @endif
        @endcan
    </div>
    @can('cashbox-list'){{ Form::open(['route' => ['cashboxes.show', $cashbox],'method'=>'GET']) }}@else{{ Form::open(['route' => ['own-cashboxes.show', $cashbox],'method'=>'GET']) }}@endcan
    <table class="table table-light table-bordered table-responsive-xs margin-tb">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Currency') }}</th>
            <th>{{ __('Sum') }}</th>
            <th>{{ __('Comment') }}</th>
            @if($cashbox->for_certificates) <th>{{ __('Certificate') }}</th> @endif
            <th>{{ __('Order') }}</th>
            <th>{{ __('Return') }}</th>
            <th>{{ __('Exchange') }}</th>
            <th>{{ __('User') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr id="searchOperations">
            <td class="p-0" area-label="{{ __('Id') }}"></td>
            <td class="p-0"
                area-label="{{ __('Date') }}"></td>
            <td class="p-0"
                area-label="{{ __('Type') }}">{{ Form::select('type', collect(['C' => __('Credit'), 'D' => __('Debit')])->prepend('--', 'not') ,Request::input('type'), ['class' => 'form-control form-control-sm selectpicker']) }}</td>
            <td class="p-0"
                area-label="{{ __('Currency') }}"></td>
            <td class="p-0"
                area-label="{{ __('Sum') }}"></td>
            <td class="p-0"
                area-label="{{ __('Comment') }}"></td>
            <td class="p-0"
                area-label="{{ __('Order') }}"></td>
            @if($cashbox->for_certificates) <td class="p-0"
                                                area-label="{{ __('Certificate') }}"></td> @endif
            <td class="p-0"
                area-label="{{ __('Return') }}"></td>
            <td class="p-0"
                area-label="{{ __('Exchange') }}"></td>
            <td class="p-0 text-center"
                area-label="{{ __('User') }}">{{ Form::button('<i class="fa fa-search"></i>', ['class' => 'btn btn-sm', 'type' => 'submit']) }}
                <a class="btn btn-sm"
                   href="@can('cashbox-list'){{ route('cashboxes.show', $cashbox) }}@else{{ route('own-cashboxes.show', $cashbox) }}@endcan"><i
                            class="fa fa-close"></i></a></td>
        </tr>
        @foreach ($operations as $key => $operation)
            <tr @if(in_array(\Auth::id(), $cashbox->userWithConfirmedRights()->pluck('user_id')->toArray()) && $operation->currentState()->is_confirmed) style="background-color: green" @endif>
                <td>{{ $operation->id }}</td>
                <td>{{ $operation->created_at }}</td>
                <td>{{ ($operation->type == 'C' ? __('Credit') : __('Debit')) }}</td>
                <td>{{ $operation->operable->name }}</td>
                <td>{{ $operation->quantity }}</td>
                <td>{{ $operation->comment }}</td>
                @if($cashbox->for_certificates)<td>{{ $operation->certificate->number }}</td> @endif
                <td>{{ $operation->order ? $operation->order->getDisplayNumber() : '' }}</td>
                <td>{{ $operation->productReturn ? $operation->productReturn->id : '' }}</td>
                <td>{{ $operation->productExchange ? $operation->productExchange->id : '' }}</td>
                <td>{{ $operation->user->name }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{Form::close()}}
    @php
        {{
        /**
          * @var $operations \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $operations->render() !!}
@endsection
