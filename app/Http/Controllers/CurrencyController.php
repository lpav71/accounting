<?php

namespace App\Http\Controllers;

use App\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    /**
     * ManufacturerController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:currency-list');
        $this->middleware('permission:currency-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:currency-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:currency-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('currencies.index', ['currencies' => Currency::orderBy('id', 'DESC')->paginate(5)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('currencies.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:currencies,name',
            'currency_rate' => 'required|numeric|min:0',
            'iso_code'=>'required|unique:currencies,iso_code',
        ]);
        
        $created = Currency::create($request->input());
        if($request->is_default){
            $notDefault = Currency::where('id','!=',$created->id)->get();
            $notDefault->map(function ($item){
                $item->is_default=0;
                $item->save();
            });
        }
        return redirect()->route('currencies.index')->with('success', 'Currency created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Currency $currency
     * @return \Illuminate\Http\Response
     */
    public function show(Currency $currency)
    {
        return view('currencies.show', compact('currency'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Currency $currency
     * @return \Illuminate\Http\Response
     */
    public function edit(Currency $currency)
    {
        return view('currencies.edit', compact('currency'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Currency $currency
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Currency $currency)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('currencies', 'name')->ignore($currency->id),
            ],
            'currency_rate' => 'required|numeric|min:0',
            'iso_code' => [
                'required',
                Rule::unique('currencies', 'iso_code')->ignore($currency->id),
            ],
        ]);

        $currency->update($request->input());
        
        if($request->is_default){
            $notDefault = Currency::where('id','!=',$currency->id)->get();
            $notDefault->map(function ($item){
                $item->is_default=0;
                $item->save();
            });
        }

        return redirect()->route('currencies.index')->with('success', 'Currency updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Currency $currency
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(Currency $currency)
    {
        if ($currency->operations()->count()) {
            return redirect()->route('currencies.index')->with('warning',
                'This Currency can not be deleted, because it\'s part of operations.');
        }

        $currency->delete();

        return redirect()->route('currencies.index')->with('success', 'Currency deleted successfully');
    }
}
