<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Класс-модель HTTP тестов.
 * 
 * Модель является владельцем конкретных реализаций типов тестов
 *
 * @package App
 * @property int $id
 * @property string $name
 * @property string $test_type
 * @property int $test_id
 * @property bool $is_active
 * @property bool $is_message
 * @property string $url
 * @property int $period
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\HttpTestIncident[] $incidents
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\HttpTest[] $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest whereIsMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest whereTestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest whereTestType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\HttpTest whereUrl($value)
 * @mixin \Eloquent
 */
class HttpTest extends Model
{
    protected $fillable = [
        'name',
        'test_type',
        'test_id',
        'is_active',
        'is_message',
        'url',
        'period',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_message' => 'boolean',
    ];

    /**
     * @var Collection
     */
    protected static $typeMap;

    /**
     * Тип теста
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function type()
    {
        return $this->morphTo('test');
    }

    /**
     * Инциденты
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function incidents() {
        return $this->hasMany(HttpTestIncident::class, 'http_test_id');
    }

    /**
     * Наименование класса теста по названию роута
     *
     * @param string $route
     * @return string|null
     */
    public static function getHttpTestClassByRoute(string $route): ?string
    {
        return self::getTypeMap()->get($route) ?? null;
    }

    /**
     * Название роута по наименованию класса теста
     *
     * @param string $class
     * @return string|null
     */
    public static function getHttpTestRouteByClass(string $class): ?string
    {
        return self::getTypeMap()->search($class) ?: null;
    }

    /**
     * Генерация названия роута по наименованию класса теста
     *
     * @param string $class
     * @return string
     */
    protected static function generateHttpTestRouteByClass(string $class): string
    {
        return Str::kebab(class_basename($class));
    }

    /**
     * Карта роутов поддерживаемых типов тестов
     *
     * @return Collection
     */
    protected static function getTypeMap(): Collection
    {
        if (is_null(self::$typeMap)) {
            self::$typeMap = collect([
                self::generateHttpTestRouteByClass(HttpFastTest::class) => HttpFastTest::class,
            ]);
        }

        return self::$typeMap;
    }
}
