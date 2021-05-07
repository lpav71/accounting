<?php

namespace App\Http\Controllers;

use App\TicketPriority;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class TicketPriorityController extends Controller
{

    /**
     * ProductAttributeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:ticketPriority-list');
        $this->middleware('permission:ticketPriority-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:ticketPriority-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:ticketPriority-delete', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $ticketPriorities = TicketPriority::orderBy('rate', 'ASC')->paginate(15);

        return response()->view('ticket-priorities.index',compact('ticketPriorities'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return response()->view('ticket-priorities.create');
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
            'name' => 'required|unique:product_attributes,name',
            'rate' => 'required|unique:product_attributes,name|integer',
        ]);

        $data = $request->input();
        $data['is_default'] = isset($data['is_default']) ? $data['is_default'] : 0;

        TicketPriority::create($data);

        return redirect()->route('ticket-priorities.index')->with('success', 'Attribute created successfully');
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param TicketPriority $ticketPriority
     * @return Response
     */
    public function edit(TicketPriority $ticketPriority)
    {
        return response()->view('ticket-priorities.edit',compact('ticketPriority'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param TicketPriority $ticketPriority
     * @return RedirectResponse
     */
    public function update(Request $request, TicketPriority $ticketPriority)
    {
        $this->validate($request, [
            'name' => Rule::unique('ticket_priorities', 'name')->ignore($ticketPriority->id),
            'rate' => Rule::unique('ticket_priorities', 'rate')->ignore($ticketPriority->id),
        ]);

        $data = $request->input();
        $data['is_default'] = isset($data['is_default']) ? $data['is_default'] : 0;
        $ticketPriority->update($data);

        return redirect()->route('ticket-priorities.index')->with('success', 'Attribute created successfully');
    }

}
