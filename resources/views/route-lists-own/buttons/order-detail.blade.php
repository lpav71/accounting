@if($nextStates->isNotEmpty())
    <div class="dropdown mt-auto mb-auto" data-container="order-detail-state-menu">
        <span id="orderDetailState{{ $orderDetail->id }}" class="btn btn-sm btn-success" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-exchange"></i>
        </span>
        <div class="dropdown-menu dropdown-menu-right p-4" aria-labelledby="orderDetailState{{ $orderDetail->id }}">
            @foreach($nextStates as $nextStateId => $nextState)
                {!! Form::button(
                preg_replace('/\s+\(.+\)/','', $nextState),
                [
                'class' => 'form-control btn btn-info'.(!$loop->last ? ' mb-3' : ''),
                'data-action' => 'order-detail-state-ajax',
                'data-url' => route('route-own-lists.action', ['orderDetail' => $orderDetail->id, 'orderDetailState' => $nextStateId])
                ]
                ) !!}
            @endforeach
        </div>
    </div>
@else
    <span class="btn btn-sm btn-outline-dark mt-auto mb-auto disabled">
        <i class="fa fa-exchange"></i>
    </span>
@endif

