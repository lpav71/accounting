<?php

namespace App\Http\Controllers;

use App\Order;
use App\Services\Tickets\Exceptions\DefaultTicketPriorityNotSetException;
use App\Services\Tickets\Exceptions\DefaultTicketStateNotSetException;
use App\Services\Tickets\Factories\TicketFactory;
use App\Services\Tickets\Repositories\TicketPriorityRepository;
use App\Services\Tickets\Repositories\TicketThemeRepository;
use App\Ticket;
use App\TicketPriority;
use App\TicketState;
use App\TicketTheme;
use App\Filters\TicketFilter;
use Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use function foo\func;

/**
 * Class TicketController
 * @package App\Http\Controllers
 */
class TicketController extends Controller
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
     * @return Response
     */
    public function index(TicketFilter $filters, Ticket $ticket, Request $request )
    {
        $tickets = $ticket->orderBy('id', 'DESC')
            ->filter($filters)
            ->get()
            ->filter(function (Ticket $ticket) {
            return $ticket->userAllowed(Auth::user());
        });
        return response()->view('tickets.index', compact('tickets'));
    }

    /**
     * @param Ticket $ticket
     */
    public function show(Ticket $ticket)
    {
        if(!$ticket->userAllowed(Auth::user())){
            return response('Unauthorized.', 403);
        }
        $ticketStates = TicketState::orderBy('id')->pluck('name', 'id');
        $ticketPriorities = TicketPriority::orderBy('rate')->pluck('name', 'id');
        $ticketThemes = TicketTheme::orderBy('id')->pluck('name', 'id');
        return view('tickets.show', compact('ticket', 'ticketStates', 'ticketPriorities', 'ticketThemes'));
    }

    /**
     * @return Response
     */
    public function create()
    {
        $priorities = TicketPriorityRepository::all()->pluck('name', 'id');

        try {
            $defaultPriorityId = TicketPriorityRepository::getDefault()->id;
        } catch (DefaultTicketPriorityNotSetException $e) {
            $defaultPriorityId = null;
        }

        $themes = TicketThemeRepository::all()->pluck('name', 'id');
        $orders = Order::getNotHidden()->pluck('order_number', 'id');
        return response()->view('tickets.create', compact('priorities', 'defaultPriorityId', 'themes', 'orders'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'ticket_priority_id' => 'required|integer',
            'ticket_theme_id' => 'required|integer',
            'order_id' => 'nullable|integer'
        ]);
        try {
            $ticket = TicketFactory::buildByIds(Auth::id(), $request->input('ticket_priority_id'), $request->input('ticket_theme_id'), null, null, $request->input('order_id'));
        } catch (DefaultTicketStateNotSetException $e) {
            throw ValidationException::withMessages([__('Default ticket state not set')]);
        }

        return response()->redirectTo(route('tickets.show', ['id' => $ticket->id]));
    }

    /**
     * @param Request $request
     * @param Ticket $ticket
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|RedirectResponse|Response
     */
    public function update(Request $request, Ticket $ticket)
    {
        if(!$ticket->userAllowed(Auth::user())){
            return response('Unauthorized.', 403);
        }
        $this->validate($request, [
            'name' => 'required',
            'ticket_priority_id' => 'required|integer',
            'ticket_theme_id' => 'required|integer',
            'ticket_state_id' => 'required|integer'
        ]);

        $ticketPriority = TicketPriority::find($request->get('ticket_priority_id'));
        $ticketTheme = TicketTheme::find($request->get('ticket_priority_id'));
        $ticketState = TicketState::find($request->get('ticket_state_id'));

        $ticket = TicketFactory::update($ticket, $request->get('name'), $ticketPriority, $ticketTheme, $ticketState);

        return response()->redirectTo(route('tickets.show', ['id' => $ticket->id]));;
    }
}
