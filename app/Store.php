<?php

namespace App;

use App\Traits\OperableStorage;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
/**
 * App\Store
 *
 * @property int $id
 * @property string $name
 * @property int|null $limit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Operation[] $operations
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Store whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Store whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Store whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Store whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $usersWithOperationRights
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $usersWithReservationRights
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $usersWithTransferRights
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Store newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Store newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Store query()
 */
class Store extends Model
{
    use OperableStorage, Sortable;

    protected $casts = [
        'is_hidden'  => 'boolean'
    ];

    protected $fillable = [
        'name',
        'limit',
        'is_hidden'
    ];

    /**
     * Сортируемые поля
     *
     * @var array
     */
    public $sortable = [
        'id',
        'name'
    ];

    /**
     * Пользователи склада
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Пользователи склада с правами на обычные операции
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function usersWithOperationRights()
    {
        return $this->belongsToMany(User::class, 'store_user_with_operation_rights');
    }

    /**
     * Пользователи склада с правами на операции резерва
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function usersWithReservationRights()
    {
        return $this->belongsToMany(User::class, 'store_user_with_reservation_rights');
    }

    /**
     * Пользователи склада с правами на операции переноса
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function usersWithTransferRights()
    {
        return $this->belongsToMany(User::class, 'store_user_with_transfer_rights');
    }

    /**
     * Товары (единицы учета) с положительным свободным остатком
     *
     * @return \Illuminate\Support\Collection
     */
    public function currentSimpleProducts()
    {
        return Product::select(['products.*', 'operations.operable_id'])
            ->selectRaw("SUM(CASE WHEN type = 'C' THEN -operations.quantity ELSE operations.quantity END) AS currentQuantity")
            ->leftJoin('operations', 'products.id', 'operations.operable_id')
            ->where('operations.operable_type', Product::class)
            ->where('operations.storage_type', Store::class)
            ->where('operations.storage_id', $this->id)
            ->groupBy('products.id')
            ->get()
            ->filter(
                function (Product $product) {
                    return $product->currentQuantity > 0;
                }
            );
    }
}
