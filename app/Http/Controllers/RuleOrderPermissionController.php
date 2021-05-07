<?php

namespace App\Http\Controllers;

use App\Carrier;
use App\CarrierGroup;
use App\Channel;
use App\OrderState;
use App\Role;
use App\User;
use App\RuleOrderPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\RuleOrderPermissionUser;
use App\RoleRuleOrderPermission;
use App\ChannelRuleOrderPermission;
use App\OrderStateRuleOrderPerm;
use App\CarrierRuleOrderPermission;
use App\CarrierGroupRuleOrderPerm;

class RuleOrderPermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('rule_order_permissions.index', ['rops' => RuleOrderPermission::orderBy('id', 'ASC')->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = User::all()->pluck('name', 'id');
        $roles = Role::all()->pluck('name', 'id');
        $channels = Channel::all()->pluck('name', 'id');
        $order_states = OrderState::all()->pluck('name', 'id');
        $carriers = Carrier::all()->pluck('name', 'id');
        $carrier_groups = CarrierGroup::all()->pluck('name', 'id');

        return view('rule_order_permissions.create', compact('users', 'roles', 'channels', 'order_states',
            'carriers', 'carrier_groups'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'user_id' => 'required_without:role_id',
            'user_id.*' => 'sometimes|required|integer',
            'role_id' => 'required_without:user_id',
            'role_id.*' => 'sometimes|required|integer',
            'channel_id.*' => 'sometimes|required|integer',
            'order_state_id.*' => 'sometimes|required|integer',
            'carier_id' => [
                function ($attribute, $value, $fail) use ($request) {
                    if (isset($request->carrier_group_id)) {
                        $fail("Выберите только службу доставки или группу доставки, что то одно");
                    }
                },
            ],
            'carier_id.*' => 'sometimes|required|integer',
            'carrier_group_id.*' => 'sometimes|required|integer',
        ]);

        DB::transaction(function () use ($request) {
            $rule_order_permission = RuleOrderPermission::create([
                'is_carrier' => !empty($request->carier_id) ? 1 : 0,
                'name' => $request->name,
            ]);

            if (isset($request->user_id)) {
                foreach ($request->user_id as $user_id) {
                    RuleOrderPermissionUser::create([
                        'rule_order_permission_id' => $rule_order_permission->id,
                        'user_id' => $user_id
                    ]);
                }
            }

            if (isset($request->role_id)) {
                foreach ($request->role_id as $role_id) {
                    RoleRuleOrderPermission::create([
                        'rule_order_permission_id' => $rule_order_permission->id,
                        'role_id' => $role_id
                    ]);
                }
            }

            if (isset($request->channel_id)) {
                foreach ($request->channel_id as $channel_id) {
                    ChannelRuleOrderPermission::create([
                        'rule_order_permission_id' => $rule_order_permission->id,
                        'channel_id' => $channel_id
                    ]);
                }
            }
            if (isset($request->order_state_id)) {
                foreach ($request->order_state_id as $order_state_id) {
                    OrderStateRuleOrderPerm::create([
                        'rule_order_permission_id' => $rule_order_permission->id,
                        'order_state_id' => $order_state_id
                    ]);
                }
            }
            if (isset($request->carier_id)) {
                foreach ($request->carier_id as $carrier_id) {
                    CarrierRuleOrderPermission::create([
                        'rule_order_permission_id' => $rule_order_permission->id,
                        'carrier_id' => $carrier_id
                    ]);
                }
            }
            if (isset($request->carrier_group_id)) {
                foreach ($request->carrier_group_id as $carrier_group_id) {
                    CarrierGroupRuleOrderPerm::create([
                        'rule_order_permission_id' => $rule_order_permission->id,
                        'carrier_group_id' => $carrier_group_id
                    ]);
                }
            }

        });

        return redirect()->route('rule-order-permission.index');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //$ruleOrderPermission = new RuleOrderPermission;
        $ruleOrderPermission = RuleOrderPermission::find($id);
        //dd($ruleOrderPermission->name);
        return view('rule_order_permissions.show', compact('ruleOrderPermission', 'users'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(RuleOrderPermission $ruleOrderPermission)
    {
        $users = User::all()->pluck('name', 'id');
        $roles = Role::all()->pluck('name', 'id');
        $channels = Channel::all()->pluck('name', 'id');
        $orderStates = OrderState::all()->pluck('name', 'id');
        $carriers = Carrier::all()->pluck('name', 'id');
        $carrier_groups = CarrierGroup::all()->pluck('name', 'id');
        return view('rule_order_permissions.edit', compact('ruleOrderPermission', 'users', 'roles', 'channels',
            'orderStates', 'carriers', 'carrier_groups'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {


            $ruleOrderPermissionUsers = new RuleOrderPermissionUser;
            $ruleOrderPermissionUsers = $ruleOrderPermissionUsers->where('rule_order_permission_id', $id)->get();
            foreach ($ruleOrderPermissionUsers as $ruleOrderPermissionUser) {
                $ruleOrderPermissionUser->delete();
            }
            if (isset($request->users)) {
                foreach ($request->users as $user_id) {
                    RuleOrderPermissionUser::create([
                        'rule_order_permission_id' => $id,
                        'user_id' => $user_id
                    ]);
                }
            }


            $roleRuleOrderPermissions = new RoleRuleOrderPermission;
            $roleRuleOrderPermissions = $roleRuleOrderPermissions->where('rule_order_permission_id', $id)->get();
            foreach ($roleRuleOrderPermissions as $roleRuleOrderPermission) {
                $roleRuleOrderPermission->delete();
            }
            if (isset($request->roles)) {
                foreach ($request->roles as $role) {
                    RoleRuleOrderPermission::create([
                        'rule_order_permission_id' => $id,
                        'role_id' => $role
                    ]);
                }
            }


            $channelRuleOrderPermissions = new ChannelRuleOrderPermission;
            $channelRuleOrderPermissions = $channelRuleOrderPermissions->where('rule_order_permission_id', $id)->get();
            foreach ($channelRuleOrderPermissions as $channelRuleOrderPermission) {
                $channelRuleOrderPermission->delete();
            }

            if (isset($request->channel_id)) {
                foreach ($request->channel_id as $channel) {
                    ChannelRuleOrderPermission::create([
                        'rule_order_permission_id' => $id,
                        'channel_id' => $channel
                    ]);
                }
            }

            //dd($request->order_state_id);

            $orderStateRuleOrderPerms = new OrderStateRuleOrderPerm;
            $orderStateRuleOrderPerms = $orderStateRuleOrderPerms->where('rule_order_permission_id', $id)->get();
            foreach ($orderStateRuleOrderPerms as $orderStateRuleOrderPerm) {
                $orderStateRuleOrderPerm->delete();
            }

            if (isset($request->order_state_id)) {
                foreach ($request->order_state_id as $order_state) {
                    OrderStateRuleOrderPerm::create([
                        'rule_order_permission_id' => $id,
                        'order_state_id' => $order_state
                    ]);
                }
            }


            $carrierRuleOrderPermissions = new CarrierRuleOrderPermission;
            $carrierRuleOrderPermissions = $carrierRuleOrderPermissions->where('rule_order_permission_id', $id)->get();
            foreach ($carrierRuleOrderPermissions as $carrierRuleOrderPermission) {
                $carrierRuleOrderPermission->delete();
            }

            if (isset($request->carier_id)) {
                foreach ($request->carier_id as $carrier_id) {
                    CarrierRuleOrderPermission::create([
                        'rule_order_permission_id' => $id,
                        'carrier_id' => $carrier_id
                    ]);
                }
            }


            $carrierGroupRuleOrderPerms = new CarrierGroupRuleOrderPerm;
            $carrierGroupRuleOrderPerms = $carrierGroupRuleOrderPerms->where('rule_order_permission_id', $id)->get();
            foreach ($carrierGroupRuleOrderPerms as $carrierGroupRuleOrderPerm) {
                $carrierGroupRuleOrderPerm->delete();
            }
            if (isset($request->carrier_group_id)) {
                foreach ($request->carrier_group_id as $carrier_group_id) {
                    CarrierGroupRuleOrderPerm::create([
                        'rule_order_permission_id' => $id,
                        'carrier_group_id' => $carrier_group_id
                    ]);
                }
            }

        });
        return redirect()->route('rule-order-permission.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ruleOrderPermission = RuleOrderPermission::find($id);
        $ruleOrderPermission->delete();
        return redirect()->route('rule-order-permission.index');
    }
}
