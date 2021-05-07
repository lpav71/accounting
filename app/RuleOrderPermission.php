<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RuleOrderPermission extends Model
{
    protected $fillable = ['name', 'is_carrier'];
    
    public function user()
    {
        return $this->belongsToMany(User::class,'rule_order_permission_users');
    }

    public function role()
    {
        return $this->belongsToMany(Role::class, 'role_rule_order_permissions');
    }

    public function channel()
    {
        return $this->belongsToMany(Channel::class,'channel_rule_order_permissions');
    }

    public function orderState()
    {
        return $this->belongsToMany(OrderState::class, 'order_state_rule_order_perms');
    }

    public function carrier()
    {
        return $this->belongsToMany(Carrier::class, 'carrier_rule_order_permissions');
    }


    public function carrierGroup()
    {
        return $this->belongsToMany(CarrierGroup::class,'carrier_group_rule_order_perms');
    }

}
