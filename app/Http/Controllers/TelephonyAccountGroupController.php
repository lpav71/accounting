<?php

namespace App\Http\Controllers;

use App\TelephonyAccount;
use App\TelephonyAccountGroup;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TelephonyAccountGroupController extends Controller
{

    /**
     * TelephonyAccountGroupController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:telephonyAccounts-list');
        $this->middleware('permission:telephonyAccounts-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:telephonyAccounts-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:telephonyAccounts-delete', ['only' => ['destroy']]);
    }


    /**
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->view('telephony-account-groups.index', ['telephonyAccountGroups' => TelephonyAccountGroup::orderBy('id', 'ASC')->paginate(25)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = User::pluck('name', 'id');
        $telephonyAccounts = TelephonyAccount::pluck('name', 'id');

        return response()->view('telephony-account-groups.create', compact('users', 'telephonyAccounts'));
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
            'name' => 'required|unique:telephony_account_groups,name',
            'user_id' => 'array',
            'telephony_account_id' => 'array'
        ]);
        $telephonyAccountGroup = TelephonyAccountGroup::create($request->input());
        $telephonyAccountGroup->users()->attach($request->input('user_id'));
        $telephonyAccountGroup->telephonyAccounts()->attach($request->input('telephony_account_id'));
        return redirect()->route('telephony-account-groups.index')->with('success', __('Created successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param TelephonyAccountGroup $telephonyAccountGroup
     * @return \Illuminate\Http\Response
     */
    public function edit(TelephonyAccountGroup $telephonyAccountGroup)
    {
        $users = User::pluck('name', 'id');
        $telephonyAccounts = TelephonyAccount::pluck('name', 'id');

        return response()->view('telephony-account-groups.edit', compact('telephonyAccountGroup', 'users', 'telephonyAccounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param TelephonyAccountGroup $telephonyAccountGroup
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, TelephonyAccountGroup $telephonyAccountGroup)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('telephony_account_groups', 'name')->ignore($telephonyAccountGroup->id)
            ],
            'user_id' => 'array',
            'telephony_account_id' => 'array'
        ]);

        $telephonyAccountGroup->update($request->input());
        $telephonyAccountGroup->users()->sync($request->input('user_id'));
        $telephonyAccountGroup->telephonyAccounts()->sync($request->input('telephony_account_id'));

        return redirect()->route('telephony-account-groups.index')->with('success', __('Updated successfully'));
    }

    /**
     * Remove the specified resource from storage.s
     *
     * @param TelephonyAccountGroup $telephonyAccountGroup
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(TelephonyAccountGroup $telephonyAccountGroup)
    {
        $telephonyAccountGroup->users()->detach();
        $telephonyAccountGroup->telephonyAccounts()->detach();
        $telephonyAccountGroup->delete();

        return redirect()->route('telephony-account-groups.index')->with('success', __('Deleted successfully'));
    }
}
