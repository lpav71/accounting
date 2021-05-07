<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketEventCriterionRequest;
use App\TicketEventCriterion;
use App\TicketPriority;
use App\TicketTheme;
use App\User;
use App\Weekday;

class TicketEventCriterionController extends Controller
{

    /**
     * ProductAttributeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:ticketEventCriteria-list');
        $this->middleware('permission:ticketEventCriteria-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:ticketEventCriteria-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:ticketEventCriteria-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ticketEventCriteria = TicketEventCriterion::orderBy('id', 'ASC')->paginate(15);

        return response()->view('ticket-event-criteria.index', compact('ticketEventCriteria'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $ticketThemes = TicketTheme::pluck('name', 'id')->prepend('','');
        $users = User::pluck('name', 'id')->prepend('','');
        $ticketPriorities = TicketPriority::pluck('name', 'id')->prepend('','');
        $lastWriters = [];
        $lastWriters[null] = null;
        foreach (config('enums.ticket_event_criteria.last_writer') as $key => $lastWriter) {
            $lastWriters[$key] = __($lastWriter);
        }
        $weekdays = Weekday::getSelect();
        return response()->view('ticket-event-criteria.create', compact('ticketThemes', 'users', 'ticketPriorities', 'lastWriters', 'weekdays'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TicketEventCriterionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TicketEventCriterionRequest $request)
    {
        $ticketEventCriterion = TicketEventCriterion::create($request->input());
        $ticketEventCriterion->weekdays()->sync($request->get('weekday_id'));

        return redirect()->route('ticket-event-criteria.index')->with('success', 'Created successfully');
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\TicketEventCriterion $ticketEventCriterion
     * @return \Illuminate\Http\Response
     */
    public function edit(TicketEventCriterion $ticketEventCriterion)
    {
        $ticketThemes = TicketTheme::pluck('name', 'id')->prepend('','');
        $users = User::pluck('name', 'id')->prepend('','');
        $ticketPriorities = TicketPriority::pluck('name', 'id')->prepend('','');
        $lastWriters = [];
        $lastWriters[null] = null;
        foreach (config('enums.ticket_event_criteria.last_writer') as $key => $lastWriter) {
            $lastWriters[$key] = __($lastWriter);
        }
        $weekdays = Weekday::getSelect();
        return response()->view('ticket-event-criteria.edit', compact('ticketEventCriterion', 'ticketThemes', 'users', 'ticketPriorities', 'lastWriters', 'weekdays'));


    }

    /**
     * Update the specified resource in storage.
     *
     * @param TicketEventCriterionRequest $request
     * @param TicketEventCriterion $ticketEventCriterion
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TicketEventCriterionRequest $request, TicketEventCriterion $ticketEventCriterion)
    {
        $ticketEventCriterion->update($request->input());
        $ticketEventCriterion->weekdays()->sync($request->get('weekday_id'));

        return redirect()->route('ticket-event-criteria.index')->with('success', 'Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\TicketEventCriterion $ticketEventCriterion
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TicketEventCriterion $ticketEventCriterion)
    {
        $ticketEventCriterion->weekdays()->detach();
        if (!$ticketEventCriterion->ticketEventSubscription->isEmpty()) {
            return redirect()->route('ticket-event-criteria.index')
                ->with('warning','This criterion can not be deleted, because it has subscribers');
        }
        $ticketEventCriterion->delete();

        return redirect()->route('ticket-event-criteria.index')->with('success', 'Deleted successfully');
    }
}
