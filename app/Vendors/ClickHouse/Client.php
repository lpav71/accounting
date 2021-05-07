<?php


namespace App\Vendors\ClickHouse;


class Client extends \ClickHouseDB\Client
{

    /**
     * Проверяет существование базы данных
     *
     * @param string $database
     * @return bool
     */
    public function isDatabaseExist(string $database): bool
    {
        return collect($this->showDatabases())->where('name', $database)->isNotEmpty();
    }

}