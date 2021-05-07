<?php

namespace App\Http\Controllers;

use App\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('city.index', ['cities' => City::orderBy('id', 'DESC')->paginate(30)]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('city.create');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'x_coordinate' => 'required|between:0,99.999999999999999999999',
            'y_coordinate' => 'required|between:0,99.999999999999999999999',
        ]);

        City::create($request->input());

        return redirect()->route('city.index')->with('success', __('City created successfully'));
    }

    /**
     * @param City $city
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(City $city)
    {
        return view('city.edit', compact('city'));
    }

    /**
     * @param Request $request
     * @param City $city
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, City $city)
    {
        $this->validate($request, [
            'name' => 'required',
            'x_coordinate' => 'required|between:0,99.999999999999999999999',
            'y_coordinate' => 'required|between:0,99.999999999999999999999',
        ]);

        $city->update($request->input());

        return redirect()->route('city.index')->with('success', __('City edit successfully'));
    }

    /**
     * @param City $city
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(City $city)
    {
        return view('city.show', compact('city'));
    }

    /**
     * @param City $city
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(City $city)
    {
        $city->delete();

        return redirect()->route('city.index')->with('success', __('City edit successfully'));
    }
}
