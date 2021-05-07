<?php

namespace App\Http\Controllers;

use App\TelephonyAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TelephonyAccountController extends Controller
{

    /**
     * TelephonyAccountController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:telephonyAccounts-list');
        $this->middleware('permission:telephonyAccounts-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:telephonyAccounts-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:telephonyAccounts-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->view('telephony-accounts.index', ['telephonyAccounts' => TelephonyAccount::orderBy('id', 'ASC')->paginate(25)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return response()->view('telephony-accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:telephony_accounts,name',
            'login' => 'required|unique:telephony_accounts,login',
            'telephony_name' =>'required|string'
        ]);
        TelephonyAccount::create($request->input());
        return redirect()->route('telephony-accounts.index')->with('success', __('Created successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param TelephonyAccount $telephonyAccount
     * @return \Illuminate\Http\Response
     */
    public function edit(TelephonyAccount $telephonyAccount)
    {
        return response()->view('telephony-accounts.edit', compact('telephonyAccount'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param TelephonyAccount $telephonyAccount
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, TelephonyAccount $telephonyAccount)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('telephony_accounts', 'name')->ignore($telephonyAccount->id)
            ],
            'login' => [
                'required',
                Rule::unique('telephony_accounts', 'login')->ignore($telephonyAccount->id)
            ],
            'telephony_name' =>'required|string'
        ]);
        $telephonyAccount->update($request->input());

        return redirect()->route('telephony-accounts.index')->with('success', __('Updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param TelephonyAccount $telephonyAccount
     * @return void
     */
    public function destroy(TelephonyAccount $telephonyAccount)
    {
        //
    }
}
