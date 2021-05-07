<?php

namespace App\Traits;

use App\Operation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait ProtectedOperable
{
    /**
     * Операции с моделью
     * 
     * @return MorphMany
     */
    protected function operations()
    {
        /**
         * @var Model $this
         */
        return $this->morphMany(Operation::class, 'operable');
    }
}
