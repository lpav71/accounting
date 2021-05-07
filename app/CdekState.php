<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Статус СДЭК
 *
 * @property int $id
 * @property string $state_code
 * @property int $need_task
 * @property string|null $task_name
 * @property string|null $task_description
 * @property int $task_date_diff
 * @property int|null $task_priority_id
 * @property int $is_daily
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property int $is_last_state
 * @property int $task_type_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CdekState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CdekState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CdekState query()
 * @mixin \Eloquent
 */
class CdekState extends Model
{
    protected $fillable = [
        'name',
        'state_code',
        'need_task',
        'task_name',
        'task_description',
        'task_date_diff',
        'task_priority_id',
        'task_type_id',
        'is_daily',
        'is_last_state',
    ];

    /**
     * Заказы
     *
     * @return mixed
     */
    public function orders()
    {
        return $this
            ->belongsToMany(Order::class)
            ->withPivot('created_at')
            ->withPivot('task_made')
            ->orderBy('cdek_state_order.id', 'desc');
    }
}
