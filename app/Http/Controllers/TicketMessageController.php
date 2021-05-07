<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketMessageCollection;
use App\Services\Tickets\Factories\TicketMessageFactory;
use App\Ticket;
use Auth;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{

    /**
     * ProductAttributeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:ticket-list');
        $this->middleware('permission:ticket-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:ticket-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:ticket-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!isset($request->get('filter')['ticket'])) {
            return response()->json([
                'errorMessage' => __('Chat id not set')
            ], 400);
        }
        if(!Ticket::find($request->get('filter')['ticket'])->userAllowed(Auth::user())){
            return response('Unauthorized.', 403);
        }
        $ticketMessages = TicketMessageFactory::getByTicketId($request->get('filter')['ticket']);

        return new TicketMessageCollection($ticketMessages);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \App\Http\Resources\TicketMessage
     */
    public function store(Request $request)
    {
        if(!Ticket::find($request->get('ticket_id'))->userAllowed(Auth::user())){
            return response('Unauthorized.', 403);
        }
        $message = TicketMessageFactory::buildByIds($request->get('text'), Auth::id(), $request->get('ticket_id'));

        return new \App\Http\Resources\TicketMessage($message);
    }


}
