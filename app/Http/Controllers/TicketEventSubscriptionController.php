<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketEventSubscriptionRequest;
use App\Services\Tickets\Events\TicketCreatedEvent;
use App\Services\Tickets\Events\TicketLastMessageTime;
use App\Services\Tickets\Events\TicketMessageAddedEvent;
use App\TicketEventAction;
use App\TicketEventCriterion;
use App\TicketEventSubscription;

class TicketEventSubscriptionController extends Controller
{

    /**
     * ProductAttributeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:ticketEventSubscriptions-list');
        $this->middleware('permission:ticketEventSubscriptions-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:ticketEventSubscriptions-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:ticketEventSubscriptions-delete', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ticketEventSubscriptions = TicketEventSubscription::orderBy('id', 'ASC')->paginate(30);

        return response()->view('ticket-event-subscriptions.index', compact('ticketEventSubscriptions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $ticketEventActions = TicketEventAction::pluck('name','id');
        $ticketEventCriteria = TicketEventCriterion::pluck('name','id');
        $ticketEvents = [
            TicketCreatedEvent::class => __('Ticket created'),
            TicketMessageAddedEvent::class => __('Ticket message added'),
            TicketLastMessageTime::class => __('Ticket last message time')
        ];
        return response()->view('ticket-event-subscriptions.create', compact('ticketEventActions', 'ticketEventCriteria', 'ticketEvents'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TicketEventSubscriptionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TicketEventSubscriptionRequest $request)
    {
        $ticketEventSubscription = TicketEventSubscription::create($request->input());
        $ticketEventSubscription->ticketEventCriteria()->sync($request->input('ticket_event_criterion_id'));
        $ticketEventSubscription->ticketEventActions()->sync($request->input('ticket_event_action_id'));

        return redirect()->route('ticket-event-subscriptions.index')->with('success', 'Attribute created successfully');
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param TicketEventSubscription $ticketEventSubscription
     * @return \Illuminate\Http\Response
     */
    public function edit(TicketEventSubscription $ticketEventSubscription)
    {
        $ticketEventActions = TicketEventAction::pluck('name','id');
        $ticketEventCriteria = TicketEventCriterion::pluck('name','id');
        $ticketEvents = [
            TicketCreatedEvent::class => __('Ticket created'),
            TicketMessageAddedEvent::class => __('Ticket message added'),
            TicketLastMessageTime::class => __('Ticket last message time')
        ];
        return response()->view('ticket-event-subscriptions.edit', compact('ticketEventSubscription','ticketEventActions', 'ticketEventCriteria', 'ticketEvents'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param TicketEventSubscriptionRequest $request
     * @param TicketEventSubscription $ticketEventSubscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TicketEventSubscriptionRequest $request, TicketEventSubscription $ticketEventSubscription)
    {
        $ticketEventSubscription->update($request->input());
        $ticketEventSubscription->ticketEventCriteria()->sync($request->input('ticket_event_criterion_id'));
        $ticketEventSubscription->ticketEventActions()->sync($request->input('ticket_event_action_id'));

        return redirect()->route('ticket-event-subscriptions.index')->with('success', 'Attribute created successfully');
    }

    /**
     * @param TicketEventSubscription $ticketEventSubscription
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(TicketEventSubscription $ticketEventSubscription)
    {
        $ticketEventSubscription->ticketEventCriteria()->detach();
        $ticketEventSubscription->ticketEventActions()->detach();

        $ticketEventSubscription->delete();

        return redirect()->route('ticket-event-subscriptions.index')->with('success', 'Deleted successfully');
    }
}
