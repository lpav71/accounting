@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Cashboxes Management') }}</h2>
            </div>
            <div class="pull-right">
                @can('cashbox-create')
                    <a class="btn btn-sm btn-success" href="{{ route('cashboxes.create') }}"> {{ __('Create New Cashbox') }}</a>
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
            <th class="text-nowrap">@sortablelink('id', __('Id'))</th>
            <th>@sortablelink('name', __('Name'))</th>
            <th>{{ __('Users') }}</th>
            <th>{{ __('Amount') }}</th>
            <th>{{ __('Transfers') }}</th>
            <th>{{ __('Confirmations') }}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($cashboxes as $key => $cashbox)
            <tr>
                <td>{{ $cashbox->id }}</td>
                <td>{{ $cashbox->name }}</td>
                <td>
                    @if(!empty($cashbox->users))
                        @foreach($cashbox->users as $v)
                            <label class="badge badge-success">{{ $v->name }}</label>
                        @endforeach
                    @endif
                </td>
                <td>
                    @foreach ($cashbox->operableIds() as $operableId)
                            @php
                                $currency = \App\Currency::find($operableId);
                            @endphp
                            <strong>{{ !is_null($currency) ? $currency->name : ''}}:</strong>
                            {{ $cashbox->getCurrentQuantity($operableId) }}
                    @endforeach
                </td>
                <td>
                    @if(!empty($cashbox->usersWithTransferRights))
                        @foreach($cashbox->usersWithTransferRights as $v)
                            <label class="badge badge-success">{{ $v->name }}</label>
                        @endforeach
                    @endif
                </td>
                <td>
                    @if(!empty($cashbox->userWithConfirmedRights))
                        @foreach($cashbox->userWithConfirmedRights as $v)
                            <label class="badge badge-success">{{ $v->name }}</label>
                        @endforeach
                    @endif
                </td>
                <td class="text-right">
                    <div class="btn-group">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('cashboxes.show',$cashbox->id) }}">{{ __('Show') }}</a>
                            @can('cashbox-edit')
                                <a class="dropdown-item" href="{{ route('cashboxes.edit',$cashbox->id) }}">{{ __('Edit') }}</a>
                                @if(!$cashbox->is_hidden)
                                <a class="dropdown-item" href="{{ route('cashbox.hide-in-menu',$cashbox->id) }}">{{ __('Hide in menu') }}</a>
                                @elseif($cashbox->is_hidden)
                                <a class="dropdown-item" href="{{ route('cashbox.show-in-menu',$cashbox->id) }}">{{ __('Show in menu') }}</a>
                                @endif
                            @endcan
                            @can('cashbox-delete')
                                {!! Form::open(['method' => 'DELETE','route' => ['cashboxes.destroy', $cashbox->id],'style'=>'display:inline']) !!}
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
          * @var $cashboxes \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $cashboxes->render() !!}
@endsection