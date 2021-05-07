<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель инцидента HTTP теста
 *
 * @package App
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 * @property int $id
 * @property int $http_test_id
 * @property \Illuminate\Support\Carbon $message_time
 * @property string $http_test_tick_type
 * @property int $http_test_tick_id
 * @property bool $is_closed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\HttpTest $test
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\HttpTestIncident[] $tick
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident whereHttpTestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident whereHttpTestTickId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident whereHttpTestTickType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident whereIsClosed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident whereMessageTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTestIncident whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class HttpTestIncident extends Model
{
    protected $fillable = [
        'http_test_id',
        'message_time',
        'http_test_tick_type',
        'http_test_tick_id',
        'is_closed',
    ];

    protected $casts = [
        'message_time' => 'datetime',
        'is_closed' => 'boolean',
    ];

    /**
     * Тест HTTP
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function test()
    {
        return $this->belongsTo(HttpTest::class, 'http_test_id');
    }

    /**
     * Первый тик инцидента
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function tick()
    {
        return $this->morphTo('http_test_tick');
    }
}
