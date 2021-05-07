<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TelephonyAccountGroup
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelephonyAccountGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelephonyAccountGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TelephonyAccountGroup query()
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\TelephonyAccount[] $telephonyAccounts
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 */
class TelephonyAccountGroup extends Model
{
    protected $fillable = [
        'name'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function telephonyAccounts()
    {
        return $this->belongsToMany(TelephonyAccount::class);
    }


}
