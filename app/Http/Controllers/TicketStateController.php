<?php

namespace App\Http\Controllers;

use App\TicketState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class TicketStateController extends Controller
{

    /**
     * ProductAttributeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:ticketState-list');
        $this->middleware('permission:ticketState-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:ticketState-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:ticketState-delete', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $ticketStates = TicketState::orderBy('id', 'ASC')->paginate(15);

        return response()->view('ticket-states.index',compact('ticketStates'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $ticketStates = TicketState::pluck('name', 'id');
        return response()->view('ticket-states.create',compact('ticketStates'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:ticket_states,name',
            'previous_ticket_states_id' => 'array'
        ]);

        $data = $request->input();

        $data['previous_ticket_states_id'] = isset($data['previous_ticket_states_id']) ? $data['previous_ticket_states_id'] : [];
        $data['is_default'] = isset($data['is_default']) ? $data['is_default'] : 0;
        $ticketState = TicketState::create($data);
        $ticketState->previousStates()->sync($data['previous_ticket_states_id']);

        return redirect()->route('ticket-states.index')->with('success', 'Attribute created successfully');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param TicketState $ticketState
     * @return Response
     */
    public function edit(TicketState $ticketState)
    {
        $ticketStates = TicketState::pluck('name', 'id');
        return response()->view('ticket-states.edit',compact('ticketState','ticketStates'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param TicketState $ticketState
     * @return RedirectResponse
     */
    public function update(Request $request, TicketState $ticketState)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('ticket_states', 'name')->ignore($ticketState->id)
            ],
            'previous_ticket_states_id' => 'array'
        ]);

        $data = $request->input();
        $data['previous_ticket_states_id'] = isset($data['previous_ticket_states_id']) ? $data['previous_ticket_states_id'] : [];
        $data['is_default'] = isset($data['is_default']) ? $data['is_default'] : 0;

        $ticketState->update($data);
        $ticketState->previousStates()->sync($data['previous_ticket_states_id']);
        return redirect()->route('ticket-states.index')->with('success', 'Attribute created successfully');
    }

}
