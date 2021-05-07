<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\VirtualOperation
 *
 * @property int $id
 * @property string $type
 * @property string $storage_type
 * @property int $storage_id
 * @property string $operable_type
 * @property int $operable_id
 * @property string $owner_type
 * @property int $owner_id
 * @property mixed $quantity
 * @property int|null $user_id
 * @property bool $is_reservation
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $operable
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $owner
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $storage
 * @property-read \App\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereIsReservation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereOperableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereOperableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereStorageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereStorageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation whereUserId($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\VirtualOperation query()
 */
class VirtualOperation extends Model
{
    const OPERATION_TYPES = [
        'C' => 'Credit',
        'D' => 'Debit',
    ];

    const OPERATION_TYPES_PRODUCT_BY_ORDER = [
        'not' => 'No',
        'C' => 'Receiving from the customer or include to return',
        'D' => 'Transfer to the customer or exclude from return',
    ];

    const OPERATION_TYPES_CURRENCY_BY_ORDER = [
        'not' => 'No',
        'C' => 'Reduction of customer debt',
        'D' => 'The increase in the debt of the customer',
    ];

    protected $casts = [
        'type' => 'string',
        'quantity' => 'decimal:2',
        'is_reservation' => 'boolean',
    ];

    protected $fillable = [
        'type',
        'storage_type',
        'storage_id',
        'operable_type',
        'operable_id',
        'owner_type',
        'owner_id',
        'quantity',
        'user_id',
        'is_reservation',
        'comment',
    ];

    /**
     * Получение хранимого объекта операции
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function operable()
    {
        return $this->morphTo();
    }

    /**
     * Получение хранилища операции
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function storage()
    {
        return $this->morphTo();
    }

    /**
     * Получение владельца операции
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function owner()
    {
        return $this->morphTo();
    }

    /**
     * Получение пользователя-автора операции
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
