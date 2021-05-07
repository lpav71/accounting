<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderCommentRequest;
use App\Order;
use App\OrderComment;
use Auth;

class OrderCommentController extends Controller
{
    public function store(OrderCommentRequest $request, Order $order)
    {
        $data = $request->input();
        $data['user_id'] = Auth::id();
        OrderComment::create($data);

        return redirect()->route('orders.edit', $order)->with('success', __('Comment added successfully'));
    }
}
