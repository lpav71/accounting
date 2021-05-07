<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OverdueTask extends Model
{
    protected $fillable = [
        'trashold'
    ];
     public function tasks()
     {
         return $this->belongsToMany('App\Task');
     }
  
}
