<?php

namespace App\Services\Telephony\Traits;


use Illuminate\Database\Eloquent\Builder;

/**
 * Trait TelephonyModelTrait
 * @package App\Services\Telephony\Traits
 */
trait TelephonyModelTrait
{

    /**
     * boot working only with it's telephony_name.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('telephony_name', function (Builder $builder) {
            $builder->where('telephony_name', '=', self::$telephonyName);
        });
    }

    /**
     * @param array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $options['telephony_name'] = self::$telephonyName;
        return parent::save($options);
    }

    /**
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        $attributes['telephony_name'] = self::$telephonyName;
        return parent::update($attributes, $options);
    }


    public function __construct(array $attributes = [])
    {
        $attributes['telephony_name'] = self::$telephonyName;
        return parent::__construct($attributes);
    }
}
