<?php

namespace App\Http\Controllers;

use App\Manufacturer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManufacturerController extends Controller
{
    /**
     * ManufacturerController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:manufacturer-list');
        $this->middleware('permission:manufacturer-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:manufacturer-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:manufacturer-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('manufacturers.index', ['manufacturers' => Manufacturer::orderBy('id', 'DESC')->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('manufacturers.create');
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
            'name' => 'required|unique:manufacturers,name'
        ]);

        Manufacturer::create($request->input());

        return redirect()->route('manufacturers.index')->with('success', 'Manufacturer created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Manufacturer  $manufacturer
     * @return \Illuminate\Http\Response
     */
    public function show(Manufacturer $manufacturer)
    {
        return view('manufacturers.show', compact('manufacturer'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Manufacturer  $manufacturer
     * @return \Illuminate\Http\Response
     */
    public function edit(Manufacturer $manufacturer)
    {
        return view('manufacturers.edit', compact('manufacturer'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Manufacturer  $manufacturer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Manufacturer $manufacturer)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('manufacturers', 'name')->ignore($manufacturer->id),
            ],
        ]);

        $manufacturer->update($request->input());

        return redirect()->route('manufacturers.index')->with('success', 'Manufacturer updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Manufacturer  $manufacturer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Manufacturer $manufacturer)
    {
        if ($manufacturer->products->count()) {
            return redirect()->route('manufacturers.index')->with('warning',
                'This Manufacturer can not be deleted, because it have products: ' . implode(', ',
                    $manufacturer->products()->pluck('name')->toArray()));
        }

        $manufacturer->delete();

        return redirect()->route('manufacturers.index')->with('success', 'Manufacturer deleted successfully');
    }
}
