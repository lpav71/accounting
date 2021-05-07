<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProductAttribute;
use Illuminate\Validation\Rule;


class ProductAttributeController extends Controller
{


    /**
     * ProductAttributeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:product-attributes-list');
        $this->middleware('permission:product-attributes-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:product-attributes-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:product-attributes-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('product-attributes.index', ['attributes' => ProductAttribute::orderBy('id', 'ASC')->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('product-attributes.create');
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
            'name' => 'required|unique:product_attributes,name',
        ]);

        ProductAttribute::create($request->input());

        return redirect()->route('product-attributes.index')->with('success', 'Attribute created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ProductAttribute $productAttribute)
    {
        return view('product-attributes.show', compact('productAttribute'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductAttribute $productAttribute)
    {
        return view('product-attributes.edit', compact('productAttribute'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProductAttribute $productAttribute)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('product_attributes', 'name')->ignore($productAttribute->id)
            ]
        ]);

        $productAttribute->update($request->input());

        return redirect()->route('product-attributes.index')->with('success', 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductAttribute $productAttribute)
    {
        if ($productAttribute->products()->count()) {
            return redirect()->route('product-attributes.index')->with('warning',
                'This attribute can not be deleted, because it has products');
        }

        $productAttribute->delete();

        return redirect()->route('product-attributes.index')->with('success', 'Attribute deleted successfully');
    }
}
