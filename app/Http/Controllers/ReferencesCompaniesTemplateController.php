<?php

namespace App\Http\Controllers;

use App\ReferencesCompaniesTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class ReferencesCompaniesTemplateController extends Controller
{


    /**
     * ReferencesCompaniesTemplateController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:references-companies-templates-list');
        $this->middleware('permission:references-companies-templates-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:references-companies-templates-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:references-companies-templates-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('references-companies-templates.index', ['templates' => ReferencesCompaniesTemplate::orderBy('id', 'ASC')->paginate(25)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('references-companies-templates.create');
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
        ReferencesCompaniesTemplate::create($request->input());
        return redirect()->route('references-companies-templates.index')->with('success', 'Attribute created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ReferencesCompaniesTemplate  $referencesCompaniesTemplate
     * @return \Illuminate\Http\Response
     */
    public function show(ReferencesCompaniesTemplate $referencesCompaniesTemplate)
    {
        return view('references-companies-templates.show', compact('referencesCompaniesTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ReferencesCompaniesTemplate  $referencesCompaniesTemplate
     * @return \Illuminate\Http\Response
     */
    public function edit(ReferencesCompaniesTemplate $referencesCompaniesTemplate)
    {
        return view('references-companies-templates.edit', compact('referencesCompaniesTemplate'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ReferencesCompaniesTemplate  $referencesCompaniesTemplate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReferencesCompaniesTemplate $referencesCompaniesTemplate)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('references_companies_templates', 'name')->ignore($referencesCompaniesTemplate->id)
            ]
        ]);

        $referencesCompaniesTemplate->update($request->input());

        return redirect()->route('references-companies-templates.index')->with('success', 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ReferencesCompaniesTemplate  $referencesCompaniesTemplate
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReferencesCompaniesTemplate $referencesCompaniesTemplate)
    {
        $referencesCompaniesTemplate->delete();

        return redirect()->route('references-companies-templates.index')->with('success', 'Attribute deleted successfully');
    }
}
