<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

/**
 * App\TaskPriority
 *
 * @property int $id
 * @property string $name
 * @property int $rate
 * @property int $is_very_urgent
 * @property int $is_normal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskPriority sortable($defaultParameters = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskPriority whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskPriority whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskPriority whereIsVeryUrgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskPriority whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskPriority whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskPriority whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskPriority newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskPriority newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskPriority query()
 * @property int $is_urgent
 */
class TaskPriority extends Model
{
    use Sortable;

    protected $fillable = [
        'name',
        'rate',
        'is_urgent',
        'is_very_urgent',
        'is_normal'
    ];
}
