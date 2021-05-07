<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleRuleOrderPermission extends Model
{
    protected $fillable = ['role_id', 'rule_order_permission_id'];
}
