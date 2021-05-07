<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CarrierGroup
 *
 * @package App
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Carrier[] $carriers
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CarrierGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CarrierGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CarrierGroup query()
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ExpenseSettings[] $expenseSettings
 */
class CarrierGroup extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function carriers()
    {
        return $this->hasMany(Carrier::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function expenseSettings()
    {
        return $this->belongsToMany(ExpenseSettings::class);
    }
}
