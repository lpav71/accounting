<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Класс-модель типа HTTP-теста - Быстрые тесты
 *
 * @package App
 * @property int $id
 * @property string $need_string_in_body
 * @property int $need_response_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\HttpTest $test
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\HttpFastTestTick[] $ticks
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTest query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTest whereNeedResponseTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTest whereNeedStringInBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class HttpFastTest extends Model
{
    protected $fillable = [
        'need_string_in_body',
        'need_response_time',
    ];

    /**
     * Тест
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function test()
    {
        return $this->morphOne(HttpTest::class, 'test');
    }

    /**
     * Тики
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ticks()
    {
        return $this->hasMany(HttpFastTestTick::class, 'http_fast_test_id');
    }
}
