<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TaskComment
 *
 * @property int $id
 * @property int $task_id
 * @property string $comment
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $author
 * @property-read \App\Task $task
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskComment whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskComment whereUserId($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TaskComment query()
 */
class TaskComment extends Model
{
    public $fillable = [
        'task_id',
        'comment',
        'user_id',
    ];

    public function author()
    {
        return $this->belongsTo('App\User', 'user_id')->withDefault();
    }

    public function task()
    {
        return $this->belongsTo('App\Task', 'task_id')->withDefault();
    }
}
