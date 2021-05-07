<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\UserWorkTable
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $time_from
 * @property \Illuminate\Support\Carbon $time_to
 * @property bool $is_working
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWorkTable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWorkTable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWorkTable query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWorkTable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWorkTable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWorkTable whereIsWorking($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWorkTable whereTimeFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWorkTable whereTimeTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWorkTable whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserWorkTable whereUserId($value)
 * @mixin \Eloquent
 */
class UserWorkTable extends Model
{
    protected $casts = [
        'time_from' => 'datetime',
        'time_to' => 'datetime',
        'is_working' => 'boolean',
    ];

    protected $fillable = [
        'user_id',
        'time_to',
        'is_working',
    ];

    /**
     * Пользователь текущего рабочего графика
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Пользователи активынх рабочих графиков
     *
     * @return User[]|Collection
     */
    public static function getActiveUsers(): Collection
    {
        return User::whereIn('id', static::getActiveWorkTables()->pluck('user_id'))->get();
    }

    /**
     * Активные рабочие графики
     *
     * @return UserWorkTable[]|Collection
     */
    public static function getActiveWorkTables(): Collection
    {
        $now = Carbon::now();

        return UserWorkTable::query()
            ->where('is_working', 1)
            ->where('time_from', '<=', $now)
            ->where('time_to', '>=', $now)
            ->orderByDesc('time_from')
            ->get();
    }


}
