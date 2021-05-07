<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\TransferIteration
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TransferIteration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TransferIteration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\TransferIteration query()
 * @mixin \Eloquent
 * @property int $id
 * @property string $settings
 * @property int $is_completed
 * @property int $transfer
 * @property int $store_id_from
 * @property int $store_id_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $transfered_count
 * @property-read \App\Store $storeFrom
 * @property-read \App\Store $storeTo
 */
class TransferIteration extends Model
{
    protected $fillable = [
        'settings',
        'is_completed',
        'transfered_count',
        'store_id_from',
        'store_id_to'
    ];

    public static function getInProcessProductIds(Store $store1, Store $store2)
    {
        $iterations = TransferIteration::where(['store_id_from' => $store1->id, 'store_id_to' => $store2->id])
            ->orWhere(['store_id_from' => $store2->id, 'store_id_to' => $store1->id])
            ->get()
            ->filter(function (TransferIteration $transferIteration){
                return !$transferIteration->is_completed;
            });
        return $iterations->flatMap(function (TransferIteration $iteration) {
            return $iteration->productIds();
        })->unique();
    }

    public function productIds()
    {
        return array_keys($this->getSettings());
    }

    public function getSettings()
    {
        return unserialize($this->settings);
    }

    public function storeFrom()
    {
        return $this->belongsTo(Store::class,'store_id_from','id');
    }

    public function storeTo()
    {
        return $this->belongsTo(Store::class,'store_id_to','id');
    }

}
