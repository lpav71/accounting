<?php

namespace App\Http\Controllers;

use App\Substitute;
use Illuminate\Http\Request;

class SubstituteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:substitutes-list');
        $this->middleware('permission:substitutes-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:substitutes-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:substitutes-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('substitutes.index', ['substitutes' => Substitute::orderBy('id', 'ASC')->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('substitutes.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'find' => 'required',
            'replace' => 'required',
        ]);

        Substitute::create($request->input());

        return redirect()->route('substitute.index')->with('success', 'Attribute created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Substitute $substitute)
    {
        return view('substitutes.show', compact('substitute'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Substitute $substitute)
    {
        return view('substitutes.edit', compact('substitute'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Substitute $substitute)
    {
        $this->validate($request, [
            'find' => 'required',
            'replace' => 'required'
        ]);

        $substitute->update($request->input());

        return redirect()->route('substitute.index')->with('success', 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Substitute $substitute)
    {
        $substitute->delete();

        return redirect()->route('substitute.index')->with('success', 'Attribute deleted successfully');
    }
}
