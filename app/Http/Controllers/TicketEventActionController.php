<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketEventActionRequest;
use App\TicketEventAction;
use App\TicketPriority;
use App\TicketTheme;
use App\User;

class TicketEventActionController extends Controller
{

    /**
     * ProductAttributeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:ticketEventActions-list');
        $this->middleware('permission:ticketEventActions-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:ticketEventActions-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:ticketEventActions-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ticketEventActions = TicketEventAction::orderBy('id', 'ASC')->paginate(15);

        return response()->view('ticket-event-action.index', compact('ticketEventActions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $ticketThemes = TicketTheme::pluck('name', 'id')->prepend('','');
        $users = User::pluck('name', 'id');
        $ticketPriorities = TicketPriority::pluck('name', 'id')->prepend('','');
        $notifiers = [];
        $notifiers[null] = null;
        foreach (config('enums.ticket_event_actions.notify') as $key => $notifier) {
            $notifiers[$key] = __($notifier);
        }
        $usersAttached = [];
        $usersNotAttached = User::where('is_not_working',0)->get()->map(function (User $user) {
            return $user->only(['id', 'name']);
        });;
        $usersNotAttachedJson = json_encode($usersNotAttached, JSON_UNESCAPED_UNICODE);
        $usersAttachedJson = json_encode($usersAttached, JSON_UNESCAPED_UNICODE);

        return response()->view('ticket-event-action.create', compact('ticketThemes', 'users', 'ticketPriorities', 'notifiers','usersAttachedJson','usersNotAttachedJson'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TicketEventActionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TicketEventActionRequest $request)
    {
        $ticketEventAction = TicketEventAction::create($request->input());
        $ticketEventAction->users()->attach(collect(json_decode($request->get('users_to_add')))->pluck('id')->toArray());

        return redirect()->route('ticket-event-actions.index')->with('success', 'Created successfully');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TicketEventAction  $ticketEventAction
     * @return \Illuminate\Http\Response
     */
    public function edit(TicketEventAction $ticketEventAction)
    {
        $ticketThemes = TicketTheme::pluck('name', 'id')->prepend('','');
        $users = User::pluck('name', 'id');
        $usersAttached = $ticketEventAction->users->map(function (User $user) {
            return $user->only(['id', 'name']);
        });
        $usersNotAttached = User::where('is_not_working',0)->whereNotIn('id', $usersAttached->pluck('id')->toArray())->get()->map(function (User $user) {
            return $user->only(['id', 'name']);
        });;
        $usersNotAttachedJson = json_encode($usersNotAttached, JSON_UNESCAPED_UNICODE);
        $usersAttachedJson = json_encode($usersAttached, JSON_UNESCAPED_UNICODE);
        $ticketPriorities = TicketPriority::pluck('name', 'id')->prepend('','');
        $notifiers = [];
        $notifiers[null] = null;
        foreach (config('enums.ticket_event_actions.notify') as $key => $notifier) {
            $notifiers[$key] = __($notifier);
        }

        return response()->view('ticket-event-action.edit', compact('ticketEventAction','ticketThemes', 'users', 'ticketPriorities', 'notifiers','usersAttachedJson','usersNotAttachedJson'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param TicketEventActionRequest $request
     * @param \App\TicketEventAction $ticketEventAction
     * @return \Illuminate\Http\Response
     */
    public function update(TicketEventActionRequest $request, TicketEventAction $ticketEventAction)
    {
        $ticketEventAction->update($request->input());
        $ticketEventAction->users()->detach();
        $ticketEventAction->users()->attach(collect(json_decode($request->get('users_to_add')))->pluck('id')->toArray());

        return redirect()->route('ticket-event-actions.index')->with('success', 'Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\TicketEventAction  $ticketEventAction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TicketEventAction $ticketEventAction)
    {
        $ticketEventAction->users()->detach();
        if (!$ticketEventAction->ticketEventSubscription->isEmpty()) {
            return redirect()->route('ticket-event-actions.index')
                ->with('warning','This action can not be deleted, because it has subscribers');
        }
        $ticketEventAction->delete();

        return redirect()->route('ticket-event-actions.index')->with('success', 'Deleted successfully');
    }
}
