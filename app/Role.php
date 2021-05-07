<?php

namespace App;

/**
 * App\Role
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_courier
 * @property bool $is_manager
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Permission[] $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @method static \Illuminate\Database\Eloquent\Builder|\Spatie\Permission\Models\Role permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role whereIsCourier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role whereIsManager($value)
 * @property bool $is_crm
 * @property bool $is_task_performer
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role whereIsCrm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Role whereIsTaskPerformer($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderState[] $orderStates
 */
class Role extends \Spatie\Permission\Models\Role
{
    public function __construct(array $attributes = [])
    {
        $this->casts['is_courier'] = 'boolean';
        $this->casts['is_manager'] = 'boolean';
        $this->casts['is_crm'] = 'boolean';
        $this->casts['is_task_performer'] = 'boolean';

        $this->fillable[] = 'name';
        $this->fillable[] = 'is_courier';
        $this->fillable[] = 'is_manager';
        $this->fillable[] = 'is_crm';
        $this->fillable[] = 'is_task_performer';
        $this->fillable[] = 'guard_name';

        parent::__construct($attributes);
    }

    /**
     * Статусы заказов с которыми может работать роль
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orderStates()
    {
        return $this->belongsToMany(OrderState::class, 'role_order_state');
    }
}
