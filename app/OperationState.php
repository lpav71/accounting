<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OperationState
 *
 * @package App
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $color
 * @property bool $is_confirmed
 * @property bool $non_confirmed
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Operation[] $operations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OperationState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OperationState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OperationState query()
 * @mixin \Eloquent
 */
class OperationState extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
      'name',
      'color',
      'is_confirmed',
      'non_confirmed'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function operations()
    {
        return $this->belongsToMany(Operation::class)->withTimestamps();
    }
}
