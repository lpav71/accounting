<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ExpenseCategory
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ExpenseSettings[] $expenseSettings
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExpenseCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExpenseCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ExpenseCategory query()
 * @mixin \Eloquent
 */
class ExpenseCategory extends Model
{
    protected $fillable = [
        'name'
    ];

    /**
     * Расходы
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function expenseSettings()
    {
        return $this->hasMany(ExpenseSettings::class);
    }
}
