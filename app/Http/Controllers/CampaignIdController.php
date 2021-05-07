<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CampaignId;

class CampaignIdController extends Controller
{


     /**
     * ProductAttributeController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:campaign-id-list');
        $this->middleware('permission:campaign-id-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:campaign-id-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:campaign-id-delete', ['only' => ['destroy']]);
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('campaign-id.index', ['campaignIds' => CampaignId::orderBy('id', 'ASC')->paginate(50)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('campaign-id.create');
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
            'campaign_id' => 'required|integer|unique:campaign_ids,campaign_id',
            'utm_campaign_id' => 'required|integer'
        ]);

        CampaignId::create($request->input());
        return redirect()->route('campaign-ids.index')->with('success', 'Attribute created successfully');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(CampaignId $campaignId)
    {
        return view('campaign-id.edit', compact('campaignId'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CampaignId $campaignId)
    {
        $this->validate($request, [
            'campaign_id' => 'required|integer|unique:campaign_ids,campaign_id',
            'utm_campaign_id' => 'required|integer'
        ]);

        $campaignId->update($request->input());
        return redirect()->route('campaign-ids.index')->with('success', 'Attribute created successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(CampaignId $campaignId)
    {
        $campaignId->delete();

        return redirect()->route('campaign-ids.index')->with('success', 'Attribute created successfully');
    }
}
