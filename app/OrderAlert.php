<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderAlert extends Model
{
    public $fillable = [
        'trashold',
    ];
    public function orders()
   {
       return $this->belongsToMany('App\Order');
   }
}
