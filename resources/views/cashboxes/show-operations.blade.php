@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Finding operations') }}</h2>
                <h4>{{__('Total amount for selected operations')}} {{ $sum }}</h4>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('cashbox.search') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
    <table class="table table-light table-bordered table-responsive-xs margin-tb">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Cashbox') }}</th>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Currency') }}</th>
            <th>{{ __('Sum') }}</th>
            <th>{{ __('Comment') }}</th>
            <th>{{ __('Order') }}</th>
            <th>{{ __('Return') }}</th>
            <th>{{ __('Exchange') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Confirm') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($operations as $operation)
            <tr class="ajax-confirm-operation" style="background-color: @if(!in_array(\Auth::id(), $operation->storage->userWithConfirmedRights()->pluck('user_id')->toArray())) {{'yellow'}} @else {{$operation->currentState()->color}} @endif">
                <td>{{ \App\Cashbox::find($operation->storage_id)->name }}</td>
                <td>{{$operation->id}}</td>
                <td>{{$operation->created_at}}</td>
                <td>{{ ($operation->type == 'C' ? __('Credit') : __('Debit')) }}</td>
                <td>{{ $operation->operable->name }}</td>
                <td>{{ $operation->quantity }}</td>
                <td>{{ $operation->comment }}</td>
                <td>{{ $operation->order ? $operation->order->getDisplayNumber() : '' }}</td>
                <td>{{ $operation->productReturn ? $operation->productReturn->id : '' }}</td>
                <td>{{ $operation->productExchange ? $operation->productExchange->id : '' }}</td>
                <td>{{ $operation->user->name }}</td>
                <td>
                    @if(in_array(\Auth::id(), $operation->storage->userWithConfirmedRights()->pluck('user_id')->toArray()))
                        {!! Form::button(__('Confirm'), array('class' => 'btn btn-block btn-success confirm-btn', 'disabled' => (boolean) $operation->currentState()->is_confirmed, 'data-url' => route('cashbox.confirm-operation', ['operation' => $operation]))) !!}
                        @endif
                </td>

            </tr>
            @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $operations \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!!
    $operations->appends(
    [
        'cashbox_id' => Request::input('cashbox_id'),
        'users_id' => Request::input('users_id'),
        'operation_type' => Request::input('operation_type'),
        'roles' => Request::input('roles'),
        'sum' => Request::input('sum'),
        'from' => Request::input('from'),
        'to' => Request::input('to'),
        'phrase' => Request::input('phrase'),
        ]
        )
        !!}
@endsection
