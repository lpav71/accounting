<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\OrderComment
 *
 * @property int $id
 * @property int $order_id
 * @property string $comment
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User|null $author
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderComment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderComment whereUserId($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderComment query()
 */
class OrderComment extends Model
{
    public $fillable = [
        'order_id',
        'comment',
        'user_id',
    ];

    public function author()
    {
        return $this->belongsTo('App\User', 'user_id')->withDefault();
    }
}
