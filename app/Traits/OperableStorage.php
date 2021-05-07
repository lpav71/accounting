<?php

namespace App\Traits;

use App\Operation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait OperableStorage
{
    /**
     * Операции хранилища
     *
     * @return MorphMany
     */
    public function operations()
    {
        return $this->morphMany(Operation::class, 'storage');
    }

    /**
     * Id хранимых объектов, по которым были операции в хранилище
     *
     * @return Collection
     */
    public function operableIds()
    {
        return $this
            ->operations()
            ->orderBy('operable_id')
            ->get()
            ->pluck('operable_id')
            ->unique();
    }

    /**
     * Свободный остаток
     *
     * @param int $operableId
     * @return int
     */
    public function getCurrentQuantity($operableId)
    {
        $currentCreditQuantity = $this
            ->operations()
            ->where('operable_id', $operableId)
            ->where('type', 'C')
            ->sum('quantity');

        $currentDebitQuantity = $this
            ->operations()
            ->where('operable_id', $operableId)
            ->where('type', 'D')
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity);
    }

    /**
     * Реальный остаток
     *
     * @param int $operableId
     * @return int
     */
    public function getRealCurrentQuantity($operableId)
    {
        $currentCreditQuantity = $this
            ->operations()
            ->where('operable_id', $operableId)
            ->where('type', 'C')
            ->where('is_reservation', 0)
            ->sum('quantity');

        $currentDebitQuantity = $this
            ->operations()
            ->where('operable_id', $operableId)
            ->where('type', 'D')
            ->where('is_reservation', 0)
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity);
    }

    /**
     * Резервный остаток
     *
     * @param int $operableId
     * @return int
     */
    public function getReservedQuantity($operableId)
    {
        $currentCreditQuantity = $this
            ->operations()
            ->where('operable_id', $operableId)
            ->where('type', 'C')
            ->where('is_reservation', 1)
            ->sum('quantity');

        $currentDebitQuantity = $this
            ->operations()
            ->where('operable_id', $operableId)
            ->where('type', 'D')
            ->where('is_reservation', 1)
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity) * -1;
    }

    /**
     * Метод для проверки остался ли зарезервированный товар на складе после изменения склада товара в заказе
     *
     * @param int $operableId
     * @param string $operableParentType
     * @param int $operableParentId
     * @return float|int
     */
    public function checkReservedQuantity(int $operableId, string $operableParentType, int $operableParentId)
    {
        $currentCreditQuantity = $this
            ->operations()
            ->where('operable_id', $operableId)
            ->where($operableParentType, $operableParentId)
            ->where('type', 'C')
            ->where('is_reservation', 1)
            ->sum('quantity');

        $currentDebitQuantity = $this
            ->operations()
            ->where('operable_id', $operableId)
            ->where($operableParentType, $operableParentId)
            ->where('type', 'D')
            ->where('is_reservation', 1)
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity) * -1;
    }

    /**
     * Свободный остаток после операции
     *
     * @param int $operableId
     * @param Operation $operation
     * @return float|int
     */
    public function getCurrentQuantityAfterOperation($operableId, Operation $operation)
    {
        $currentCreditQuantity = $this
            ->operations()
            ->where('id', '<=', $operation->id)
            ->where('operable_id', $operableId)
            ->where('type', 'C')
            ->sum('quantity');

        $currentDebitQuantity = $this
            ->operations()
            ->where('id', '<=', $operation->id)
            ->where('operable_id', $operableId)
            ->where('type', 'D')
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity);
    }

    /**
     * Реальный остаток после операции
     *
     * @param int $operableId
     * @param Operation $operation
     * @return float|int
     */
    public function getRealCurrentQuantityAfterOperation($operableId, Operation $operation)
    {
        $currentCreditQuantity = $this
            ->operations()
            ->where('id', '<=', $operation->id)
            ->where('operable_id', $operableId)
            ->where('type', 'C')
            ->where('is_reservation', 0)
            ->sum('quantity');

        $currentDebitQuantity = $this
            ->operations()
            ->where('id', '<=', $operation->id)
            ->where('operable_id', $operableId)
            ->where('type', 'D')
            ->where('is_reservation', 0)
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity);
    }

    /**
     * Резервный остаток после операции
     *
     * @param int $operableId
     * @param Operation $operation
     * @return float|int
     */
    public function getReservedQuantityAfterOperation($operableId, Operation $operation)
    {
        $currentCreditQuantity = $this
            ->operations()
            ->where('id', '<=', $operation->id)
            ->where('operable_id', $operableId)
            ->where('type', 'C')
            ->where('is_reservation', 1)
            ->sum('quantity');

        $currentDebitQuantity = $this
            ->operations()
            ->where('id', '<=', $operation->id)
            ->where('operable_id', $operableId)
            ->where('type', 'D')
            ->where('is_reservation', 1)
            ->sum('quantity');

        return ($currentDebitQuantity - $currentCreditQuantity) * -1;
    }

    /**
     * Остаток перед операцией
     *
     * @param $operableId
     * @param Operation $operation
     * @return float|int
     */
    public function getQuantityBeforeOperation($operableId, Operation $operation)
    {
        $creditQuantity = $this
            ->operations()
            ->where('id', '<', $operation->id)
            ->where('operable_id', $operableId)
            ->where('type', 'C')
            ->sum('quantity');

        $debitQuantity = $this
            ->operations()
            ->where('id', '<', $operation->id)
            ->where('operable_id', $operableId)
            ->where('type', 'D')
            ->sum('quantity');

        return $debitQuantity - $creditQuantity;
    }
}
