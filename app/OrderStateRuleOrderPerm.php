<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderStateRuleOrderPerm extends Model
{
    protected $fillable = ['order_state_id', 'rule_order_permission_id'];
}
