<?php

namespace App\Services\TripleFilterService;

/**
 * Сервис для работы тройного мультиселект фильтра
 *
 * Class TripleFilterService
 * @package App\Services\TripleFilterService
 */
class TripleFilterService
{
    /**
     * Добавление имени объекта к value
     *
     * @param array $arr
     * @param string $objectName
     * @return array
     */
    public static function addObjectTypeToValue(array $arr, string $objectName)
    {
        $newArr = [];
        foreach ($arr as $key => $value) {
            $newArr[$objectName . '_' . $key] = $value;
        }

        return $newArr;
    }

    /**
     * Парсинг value из фильтра статусов
     *
     * @param array $arr
     * @return array
     */
    public static function parseRequestInputFilter(array $arr)
    {
        $newArr = [];
        foreach ($arr as $value) {
            $tmp = explode('_',$value);
            $newArr[$tmp[0]][] = $tmp[1];
        }

        return $newArr;
    }
}