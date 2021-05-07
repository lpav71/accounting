<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TelephonyAccount
 *
 * @property int $id
 * @property string $name
 * @property string $login
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelephonyAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelephonyAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelephonyAccount query()
 * @mixin \Eloquent
 * @property string $telephony_name
 */
class TelephonyAccount extends Model
{

    protected $fillable = [
        'name',
        'login',
        'telephony_name'
    ];

}
