@extends('layouts.app')

@section('content')
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
    {{ Form::open(['method'=>'GET']) }}
    <table class="table table-light table--responsive table-sm table-bordered table-striped small-1 order-table">
        <thead class="thead-light">
        <tr>
            <th scope="col" class="text-nowrap">@sortablelink('id', __('Id'))</th>
            <th scope="col" class="text-nowrap">@sortablelink('order_number', __('Id #1'))</th>
            <th scope="col" class="text-nowrap">@sortablelink('created_at', __('Date'))</th>
            <th scope="col">{{ __('Channel') }}</th>
            <th scope="col">{{ __('Customer') }}</th>
            <th scope="col">{{ __('Phone') }}</th>
            <th scope="col">{{ __('Shipping') }}</th>
            <th scope="col">{{ __('City') }}</th>
            <th scope="col">{{ __('Address') }}</th>
            <th scope="col">{{ __('Comment') }}</th>
            <th scope="col" class="text-nowrap">@sortablelink('date_estimated_delivery', __('Deliv.'))</th>
            <th scope="col" class="text-nowrap">@sortablelink('delivery_start_time', __('St.'))</th>
            <th scope="col" class="text-nowrap">@sortablelink('delivery_end_time', __('En.'))</th>
            <th scope="col">{{ __('State') }}</th>
            <th scope="col" style="min-width: 200px;">{{ __('Tasks') }}</th>
            <th class="text-center">
                <a class="btn btn-outline-primary btn-sm" data-toggle="collapse" data-target=".multi-collapse">
                    Информация
                </a>
            </th>
        </tr>
        </thead>
        <tbody>
        <tr id="searchOrders">
            <td class="p-0" area-label="{{ __('Id') }}">{{ Form::text('id', Request::input('id'), ['class' => 'form-control form-control-sm search-input text-center', 'size' => 2]) }}</td>
            <td class="p-0" area-label="{{ __('Id #1') }}">{{ Form::text('orderNumber', Request::input('orderNumber'), ['class' => 'form-control form-control-sm search-input text-center', 'size' => 2]) }}</td>
            <td class="p-0" area-label="{{ __('Date') }}">{!! Form::text('date', Request::input('date'), ['class' => 'form-control form-control-sm date search-input text-center', 'size' => 3]) !!}</td>
            <td class="p-0" area-label="{{ __('Channel') }}">{{ Form::select('channel', \App\Channel::where('is_hidden',0)->pluck('name', 'id')->prepend('--', 0), Request::input('channel'), ['class' => 'form-control form-control-sm selectpicker']) }}</td>
            <td class="p-0" area-label="{{ __('Customer') }}"><select data-async-select-url='{{route('customers.async.selector')}}', class="form-control async-select form-control-sm", data-async-select-loaded="0", data-async-select-id="{{Request::input('customer')}}" name="customer"></select></td>
            <td class="p-0" area-label="{{ __('Phone') }}">{{ Form::text('phone', Request::input('phone'), ['class' => 'form-control form-control-sm search-input', 'size' => 3]) }}</td>
            <td class="p-0" area-label="{{ __('Carrier') }}">{{ Form::select('carrier[]', \App\Carrier::pluck('name', 'id')->prepend('--', 0) , Request::input('carrier'), ['class' => 'form-control form-control-sm  selectpicker', 'multiple' => 'multiple']) }}</td>
            <td class="p-0" area-label="{{ __('City') }}">{{ Form::text('filter-delivery_city', Request::input('filter-delivery_city'), ['class' => 'form-control form-control-sm search-input']) }}</td>
            <td class="p-0" area-label="{{ __('Address') }}">{{ Form::text('address', Request::input('address'), ['class' => 'form-control form-control-sm search-input']) }}</td>
            <td class="p-0" area-label="{{ __('Comment') }}"></td>
            <td class="p-0" area-label="{{ __('Delivery') }}">{!! Form::text('dateDelivery', Request::input('dateDelivery'), ['class' => 'form-control form-control-sm date search-input text-center', 'size' => 3]) !!}</td>
            <td class="p-0" area-label="{{ __('Start Delivery time') }}"></td>
            <td class="p-0" area-label="{{ __('End Delivery time') }}"></td>
            <td class="p-0" area-label="{{ __('State') }}">{{ Form::select('state[]', $orderStates, Request::input('state'), ['multiple' => true,'class' => 'form-control form-control-sm selectpicker dropleft']) }}</td>
            <td class="p-0" area-label="{{ __('Tasks') }}"></td>
            <td class="text-center p-1">
                {{ Form::button('<i class="fa fa-search"></i>', ['class' => 'btn btn-sm', 'type' => 'submit']) }}
                <a class="btn btn-sm" href="{{ route('orders.index') }}"><i class="fa fa-close"></i></a>
            </td>
        </tr>
        @foreach ($orders as $key => $order)
            <tr style="background-color: {{ !empty($order->currentState())?$order->currentState()['color']: '#FFFFFF' }};">
                <td area-label="{{ __('Id') }}">
                    <a href="{{ route('orders.edit', $order) }}" class="text-dark">{{ $order->getDisplayNumber() }}</a>
                </td>
                <td area-label="{{ __('Id #1') }}">
                    <a href="{{ route('orders.edit', $order) }}" class="text-dark">{{ $order->order_number }}</a>
                </td>
                <td area-label="{{ __('Date') }}" class="text-nowrap">
                    <div>{{ $order->created_at->format('d-m-Y') }}</div>
                    <div>{{ $order->created_at->format('H:i') }}</div>
                </td>
                <td area-label="{{ __('Channel') }}">
                    {{ $order->channel->name }}
                    @if($order->clientID || $order->gaClientID || $order->utm_campaign)
                        <div class="d-block">
                            @if($order->clientID || $order->gaClientID)
                                <div class="badge border border-dark mr-1">id</div>
                            @endif
                            @if($order->utm_campaign)
                                <div class="badge border border-dark mr-1">utm</div>
                            @endif
                            @switch($order->age)
                                @case('17')
                                <div class="badge border border-dark bg-warning mr-1">< 18</div>
                                @break
                                @case('18')
                                <div class="badge border border-dark bg-success mr-1">18 - 24</div>
                                @break
                                @case('25')
                                <div class="badge border border-dark bg-success mr-1">25 - 34</div>
                                @break
                                @case('35')
                                <div class="badge border border-dark bg-success mr-1">35 - 44</div>
                                @break
                                @case('45')
                                <div class="badge border border-dark bg-success mr-1">45 - 54</div>
                                @break
                                @case('55')
                                <div class="badge border border-dark bg-success mr-1">55 <</div>
                                @break
                            @endswitch
                            @switch($order->device)
                                @case('mobile')
                                <i class="fa fa-mobile-phone fa-2x align-middle"></i>
                                @break
                                @case('tablet')
                                <i class="fa fa-tablet fa-2x align-middle"></i>
                                @break
                                @case('desktop')
                                <i class="fa fa-desktop align-middle"></i>
                                @break
                            @endswitch
                            @switch($order->gender)
                                @case('male')
                                <i class="fa fa-male fa-2x align-middle" style="font-size: 1.6em;"></i>
                                @break
                                @case('female')
                                <i class="fa fa-female fa-2x align-middle" style="font-size: 1.6em;"></i>
                                @break
                            @endswitch
                        </div>
                    @endif</td>
                <td area-label="{{ __('Customer') }}">{{ $order->customer->first_name }} {{ $order->customer->last_name }}</td>
                <td area-label="{{ __('Phone') }}"><a href="{{route('orders.index', ['phone' => $order->customer->phone])}}" class="text-reset">{{ $order->customer->phone }}</a></td>
                <td area-label="{{ __('Carrier') }}">{{ $order->carrier ? $order->carrier->name : '' }}</td>
                <td area-label="{{ __('City') }}">{{ str_replace(',', ', ', $order->delivery_city) }}</td>
                <td area-label="{{ __('Address') }}">{{ $order->getStreetDeliveryAddress() }}</td>
                <td area-label="{{ __('Comment') }}">{!! preg_replace('|http[s]{0,1}://([a-zA-Z0-9-./_\?\+=&%]+)|', '<a href="http://$1" target="_blank"><i class="fa fa-link text-dark"></i></a>', $order->comment) !!}</td>
                <td area-label="{{ __('Delivery') }}" style="width: 10px;" class="text-nowrap">{{ $order->date_estimated_delivery }}
                </td>
                <td area-label="{{ __('Start Delivery time') }}" style="width: 10px;">{{ $order->delivery_start_time }}</td>
                <td area-label="{{ __('End Delivery time') }}" style="width: 10px;">{{ $order->delivery_end_time }}</td>
                <td area-label="{{ __('State') }}">{{ !empty($order->currentState()) ? $order->currentState()['name'] : 'not set' }}</td>
                <td area-label="{{ __('Tasks') }}">
                    @if($order->openTasks()->count())
                        <ul class="p-0">
                            @foreach($order->openTasks() as $task)
                                <li class="badge-warning list-unstyled mb-1 p-1">
                                    <a href="{{ route('tasks.edit', $task) }}" class="text-dark" target="_blank">@if($task->deadline_date)
                                            <span class="text-nowrap">{{$task->deadline_date}} {{$task->deadline_time}}:</span> @endif{{ $task->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </td>
                <td class="not-head text-center">
                    <a class="btn btn-outline-primary btn-sm" data-toggle="collapse" data-target="#order-{{ $order->id }}">
                        <i class="fa fa-info"></i>
                    </a>
                    <div class="btn-group dropleft">
                        <a class="btn btn-secondary btn-sm dropdown-toggle" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-plus"></i>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ route('orders.show',$order->id) }}">{{ __('Show') }}</a>
                            @can('order-edit')
                                <a class="dropdown-item" href="{{ route('orders.edit',$order->id) }}">{{ __('Edit') }}</a>
                                <a class="dropdown-item" href="{{ route('tasks.create.from.order',$order->id) }}" target="_blank">{{ __('Create task by Order') }}</a>
                                <a class="dropdown-item" href="{{ route('tasks.create.from.customer',$order->customer->id) }}" target="_blank">{{ __('Create task by Customer') }}</a>
                            @endcan
                            <a class="dropdown-item" href="{{ route('orders.pdf.get',$order->id) }}">{{ __('Print') }}</a>
                        </div>
                    </div>
                </td>
            </tr>
            <tr class="collapse multi-collapse" id="order-{{ $order->id }}">
                <td colspan="13" class="not-head">
                    <ul class="p-2">
                        @foreach($order->orderDetails as $v)
                            <li class="list-unstyled">
                                <label class="@if($v->product->need_guarantee) badge-success @else badge-info @endif p-1">{{ $v->product->name }}</label>
                                - {{ $v->price }} [{{ $v->currency->name }}]
                            </li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{Form::close()}}
    @php
        {{
        /**
          * @var $orders \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $orders->render() !!}
@endsection
