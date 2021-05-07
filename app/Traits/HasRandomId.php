<?php

namespace App\Traits;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Добавляет случайную генерацию целых ключей. Используется только в классах моделей.
 *
 * При реализации обрабатывает свойство $randomIds модели,
 * в котором указан массив "случайных" ключей в виде ['имя ключа' => длина ключа].
 *
 * Будьте аккуратны с длиной ключа >= 20 (64 бит)
 *
 * @method static Builder|Model where($name, $value)
 * @method Connection getConnection()
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
trait HasRandomId
{
    /**
     * Возвращает длину "случайного" ключа
     *
     * @param string $name
     *
     * @return int
     */
    protected function getRandomIdLength(string $name): int
    {
        return $this->randomIds[$name] ?? 0;
    }

    /**
     * Генерация нового уникального значения для "случайного" ключа.
     *
     * Будьте аккуратны в случае, если модель уже существует в БД и имеет отношения по данному ключу,
     * т.к. метод меняет значение только в самой модели.
     *
     * @param string $name
     *
     * @return int
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     *
     */
    public function generateRandomId(string $name): int
    {
        $this->checkRandomIdDeclaration($name);

        $length = $this->getRandomIdLength($name);

        do {
            $id = random_int("1".str_repeat("0", $length - 1), str_repeat("9", $length));
        } while (static::where($name, $id)->exists());

        $this->$name = $id;

        return $id;
    }

    /**
     * Проверяет декларацию "случайного" ключа и его наличие в модели
     *
     * При нарушении условий - возбуждает исключение
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    protected function checkRandomIdDeclaration(string $name)
    {
        if (!key_exists($name, $this->randomIds)) {
            throw new \InvalidArgumentException(
                sprintf('Key \'%s\' is not exists in randomKeys property of class %s.', $name, get_class($this))
            );
        }

        if (!is_int($this->randomIds[$name]) || 0 >= $this->randomIds[$name]) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Key \'%s\' do not have right length value in randomKeys property of class %s.',
                    $name,
                    get_class($this)
                )
            );
        }
    }

    /**
     * Выполняет операцию вставки
     *
     * @param Builder $query
     *
     * @return bool
     * @throws \Throwable
     */
    protected function performInsert(Builder $query)
    {
        $result = false;

        $this->getConnection()->transaction(
            function () use ($query, &$result) {
                foreach (array_keys($this->randomIds) as $name) {
                    $this->generateRandomId($name);
                }

                $result = parent::performInsert($query);
            }
        );

        return $result;
    }
}
