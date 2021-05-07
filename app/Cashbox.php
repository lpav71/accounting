<?php

namespace App;

use App\Traits\OperableStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Kyslik\ColumnSortable\Sortable;
/**
 * App\Cashbox
 *
 * @property int $id
 * @property string $name
 * @property int $limit
 * @property int $operation_limit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Operation[] $operations
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $usersWithTransferRights
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cashbox whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cashbox whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cashbox whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cashbox whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cashbox newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cashbox newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cashbox query()
 * @property bool $is_non_cash
 * @property bool $for_certificates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Cashbox whereIsNonCash($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $userWithConfirmedRights
 */
class Cashbox extends Model
{
    use OperableStorage, Sortable;

    protected $casts = [
        'is_non_cash' => 'boolean',
        'is_hidden'  => 'boolean'
    ];

    protected $fillable = [
        'name',
        'is_non_cash',
        'limit',
        'operation_limit',
        'for_certificates',
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
     * Пользователи
     *
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Пользователи касс с правами на операции переноса
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function usersWithTransferRights()
    {
        return $this->belongsToMany(User::class, 'cashbox_user_with_transfer_rights');
    }

    /**
     * Пользователи касс с правами на подверждение операций
     *
     * @return BelongsToMany
     */
    public function userWithConfirmedRights()
    {
        return $this->belongsToMany(User::class, 'cashbox_user_with_confirmed_rights');
    }
}
