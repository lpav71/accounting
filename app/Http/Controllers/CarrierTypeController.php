<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CarrierType;
use Illuminate\Validation\Rule;

/**
 * Class CarrierTypeController
 * @package App\Http\Controllers
 */
class CarrierTypeController extends Controller
{

    /**
     * CarrierTypeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:carrier-types-list');
        $this->middleware('permission:carrier-types-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:carrier-types-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:carrier-types-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('carrier-types.index', ['carrierTypes' => CarrierType::orderBy('id', 'DESC')->paginate(30)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('carrier-types.create');
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
            'name' => 'required|unique:carrier_types,name',
        ]);

        CarrierType::create($request->input());

        return redirect()->route('carrier-types.index')->with('success', __('Carrier created successfully'));
    }


    /**
     * @param CarrierType $carrierType
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(CarrierType $carrierType)
    {
        return view('carrier-types.show', compact('carrierType'));
    }


    /**
     * @param CarrierType $carrierType
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(CarrierType $carrierType)
    {
        return view('carrier-types.edit', compact('carrierType'));
    }


    /**
     * @param Request $request
     * @param CarrierType $carrierType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CarrierType $carrierType)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('carrier_types', 'name')->ignore($carrierType->id),
            ]
        ]);
        $carrierType->update($request->input());
        return redirect()->route('carrier-types.index')->with('success', __('Carrier type updated successfully'));
    }


    /**
     * @param CarrierType $carrierType
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(CarrierType $carrierType)
    {
        $carrierType->delete();

        return redirect()->route('carrier-types.index')->with('success', 'Carrier deleted successfully');
    }
}
