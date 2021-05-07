<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

/**
 * Тип задачи
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 * @property int $id
 * @property string $name
 * @property string $color
 * @property int $is_store
 * @property int $is_basic
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $balancerDisabledUsers
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Role[] $balancerPriorityRoles
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $balancerPriorityUsers
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Task[] $tasks
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskType whereIsStore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskType sortable($defaultParameters = null)
 * @mixin \Eloquent
 */
class TaskType extends Model
{
    use Sortable;

    protected $fillable = [
        'name',
        'color',
        'is_store',
        'is_basic'
    ];

    /**
     * Задачи текущего типа
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Приоритетные пользователи для распределения задач данного типа
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function balancerPriorityUsers()
    {
        return $this->belongsToMany(User::class, 'task_type_user_balancer', 'task_type_id', 'user_id');
    }

    /**
     * Приоритетные роли пользователей для распределения задач данного типа
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function balancerPriorityRoles()
    {
        return $this->belongsToMany(Role::class, 'task_type_role_balancer', 'task_type_id', 'role_id');
    }

    /**
     * Пользователи, для задач которых выключено распределение
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function balancerDisabledUsers()
    {
        return $this->belongsToMany(User::class, 'task_type_user_disabled_balancer', 'task_type_id', 'user_id');
    }
}
