<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Substitute extends Model
{
    protected $fillable = ['find','replace'];
}