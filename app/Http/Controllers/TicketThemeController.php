<?php

namespace App\Http\Controllers;

use App\TicketTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Response;

class TicketThemeController extends Controller
{

    /**
     * ProductAttributeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:ticketTheme-list');
        $this->middleware('permission:ticketTheme-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:ticketTheme-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:ticketTheme-delete', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ticketCategories = TicketTheme::orderBy('id', 'ASC')->paginate(15);

        return response()->view('ticket-themes.index',compact('ticketCategories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return response()->view('ticket-themes.create');
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
            'name' => 'required|unique:ticket_themes,name',
            'is_hidden' => 'nullable'
        ]);

        TicketTheme::create($request->input());

        return redirect()->route('ticket-themes.index')->with('success', 'Attribute created successfully');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param TicketTheme $ticketTheme
     * @return \Illuminate\Http\Response
     */
    public function edit(TicketTheme $ticketTheme)
    {
        return response()->view('ticket-themes.edit',compact('ticketTheme'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param TicketTheme $ticketTheme
     * @return RedirectResponse
     */
    public function update(Request $request, TicketTheme $ticketTheme)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('ticket_themes', 'name')->ignore($ticketTheme->id)
            ]
        ]);
        $data = $request->input();
        $data['is_hidden'] = isset($data['is_hidden']) ? $data['is_hidden'] : 0;
        $ticketTheme->update($data);

        return redirect()->route('ticket-themes.index')->with('success', 'Attribute created successfully');
    }
}
