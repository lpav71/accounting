<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProductCharacteristic;
use Illuminate\Validation\Rule;

class ProductCharacteristicController extends Controller
{
    /**
     * ProductCharacteristicController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:product-characteristics-list');
        $this->middleware('permission:product-characteristics-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:product-characteristics-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:product-characteristics-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('product-characteristics.index', ['characteristics' => ProductCharacteristic::orderBy('id', 'ASC')->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('product-characteristics.create');
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
            'name' => 'required|unique:product_characteristics,name',
        ]);

        ProductCharacteristic::create($request->input());

        return redirect()->route('product-characteristics.index')->with('success', 'Category created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ProductCharacteristic $productCharacteristic)
    {
        return view('product-characteristics.show', compact('productCharacteristic'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductCharacteristic $productCharacteristic)
    {
        return view('product-characteristics.edit', compact('productCharacteristic'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductCharacteristic $productCharacteristic)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('product_characteristics', 'name')->ignore($productCharacteristic->id)
            ]
        ]);

        $productCharacteristic->update($request->input());

        return redirect()->route('product-characteristics.index')->with('success', 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductCharacteristic $productCharacteristic)
    {
        if ($productCharacteristic->products()->count()) {
            return redirect()->route('product-characteristics.index')->with('warning',
                'This characteristic can not be deleted, because it has products');
        }

        $productCharacteristic->delete();

        return redirect()->route('product-characteristics.index')->with('success', 'Characteristic deleted successfully');
    }
}
