<?php

namespace App\Traits;

use App\VirtualOperation;

/**
 * Trait OperableVirtualMultipleStorage
 * Расширение модели до мультихранилища, способного содержать виртуальные операции нескольких типов хранимых моделей
 *
 * @package App\Traits
 */
trait OperableVirtualMultipleStorage
{
    /**
     * Получение операций по хранилищу
     *
     * @param string $operable_type
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function virtualOperations($operable_type = null)
    {
        /**
         * @var $this \Illuminate\Database\Eloquent\Model
         */
        return is_null($operable_type) ?
            $this->morphMany(VirtualOperation::class, 'storage')
            :
            $this->morphMany(VirtualOperation::class, 'storage')
                ->where('operable_type', $operable_type);
    }

    /**
     * Получение ID хранимых моделей
     *
     * @param string $operable_type
     * @return \Illuminate\Support\Collection
     */
    public function virtualOperableIds($operable_type)
    {
        return $this
            ->virtualOperations($operable_type)
            ->orderBy('operable_id')
            ->get()
            ->keyBy('operable_id')
            ->keys();
    }

    /**
     * Получение текущего свободного количества единиц хранимых моделей
     *
     * @param integer $operableId
     * @param string $operable_type
     * @return integer|float
     */
    public function getFreeVirtualQuantity($operableId, $operable_type)
    {
        $currentCreditQuantity = $this
            ->virtualOperations($operable_type)
            ->where('operable_id', $operableId)
            ->where('type', 'C')
            ->sum('quantity');

        $currentDebitQuantity = $this
            ->virtualOperations($operable_type)
            ->where('operable_id', $operableId)
            ->where('type', 'D')
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity);
    }

    /**
     * Получение реального количества единиц хранимых моделей (резерв + свободные)
     *
     * @param $operableId integer
     * @param string $operable_type
     * @return integer
     */
    public function getRealVirtualQuantity($operableId, $operable_type)
    {
        $currentCreditQuantity = $this
            ->virtualOperations($operable_type)
            ->where('operable_id', $operableId)
            ->where('type', 'C')
            ->where('is_reservation', 0)
            ->sum('quantity');

        $currentDebitQuantity = $this
            ->virtualOperations($operable_type)
            ->where('operable_id', $operableId)
            ->where('type', 'D')
            ->where('is_reservation', 0)
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity);
    }

    /**
     * Получение текущего количества единиц хранимых моделей, находящихся в резерве
     *
     * @param $operableId integer
     * @param string $operable_type
     * @return integer
     */
    public function getReservedVirtualQuantity($operableId, $operable_type)
    {
        $currentCreditQuantity = $this
            ->virtualOperations($operable_type)
            ->where('operable_id', $operableId)
            ->where('type', 'C')
            ->where('is_reservation', 1)
            ->sum('quantity');

        $currentDebitQuantity = $this
            ->virtualOperations($operable_type)
            ->where('operable_id', $operableId)
            ->where('type', 'D')
            ->where('is_reservation', 1)
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity) * -1;
    }
}
