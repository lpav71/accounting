<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TelegramReportSetting
 *
 * @property int $id
 * @property string $account_id
 * @property string $time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $name
 * @property string $chat_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderState[] $orderStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TaskState[] $taskStates
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramReportSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramReportSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramReportSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramReportSetting whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramReportSetting whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramReportSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramReportSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramReportSetting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramReportSetting whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelegramReportSetting whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string $confirm_time
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderDetailState[] $orderDetailStates
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 */
class TelegramReportSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $fillable = [
        'name',
        'time',
        'chat_id',
        'confirm_time'
    ];

    /**
     * get taskStates
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function taskStates()
    {
        return $this->belongsToMany('App\TaskState');
    }

    /**
     * get orderStates
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orderStates()
    {
        return $this->belongsToMany('App\OrderState');
    }

    /**
     * get orderDetailStates
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orderDetailStates()
    {
        return $this->belongsToMany('App\OrderDetailState');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
