<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель тика быстрого теста HTTP
 *
 * @package App
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 * @property int $id
 * @property int $http_fast_test_id
 * @property int $response_time
 * @property bool $is_have_need_string_in_body
 * @property bool $is_finished
 * @property bool $is_error
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\HttpFastTest $test
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick whereHttpFastTestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick whereIsError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick whereIsFinished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick whereIsHaveNeedStringInBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick whereResponseTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpFastTestTick whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class HttpFastTestTick extends Model
{
    protected $fillable = [
        'http_fast_test_id',
        'response_time',
        'is_have_need_string_in_body',
        'is_finished',
        'is_error',
        'message',
        'created_at',
    ];

    protected $casts = [
        'is_have_need_string_in_body' => 'boolean',
        'is_finished' => 'boolean',
        'is_error' => 'boolean',
    ];

    /**
     * Тест HTTP
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function test()
    {
        return $this->belongsTo(HttpFastTest::class, 'http_fast_test_id');
    }
}
