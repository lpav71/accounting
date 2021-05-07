<?php

namespace App\Traits;

trait Operable
{
    /**
     * Get Operations for Model
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function operations()
    {
        /**
         * @var $this \Illuminate\Database\Eloquent\Model
         */
        return $this->morphMany('App\Operation', 'operable');
    }
}
