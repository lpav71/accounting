<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RuleOrderPermissionUser extends Model
{
    protected $fillable = ['user_id', 'rule_order_permission_id'];
}
