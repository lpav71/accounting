<?php

namespace App\Filters;

use App\Customer;
use Kyslik\LaravelFilterable\Generic\Filter;
use Carbon\Carbon;

class CustomerFilter extends Filter
{
    /**
     * Конечные свойства модели, по которым может идти сортировка
     *
     * @var array
     */
    protected $filterables = ['id'];

    /**
     * Карта фильтров
     *
     * @return array
     */
    public function filterMap(): array
    {
        return [
            'id' => ['id'],
            'first_name' => ['first_name'],
            'last_name' => ['last_name'],
            'phone' => ['phone'],
            'email' => ['email'],
        ];
    }

    /**
     * Фильтр по id
     *
     * @param int $Id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function id($Id = 0)
    {
        return $Id ? $this->builder->where('id', $Id) : $this->builder;
    }

    /**
     * Фильтр по Имени
     *
     * @param int $first_name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function first_name($first_name = 0)
    {
        if($first_name != 0){
        $first_name=Customer::distinct()->orderBy('first_name')->pluck('first_name')->prepend('--', 0)[$first_name];
        return $this->builder->whereIn(
            'id',
            Customer::where('first_name', $first_name)->pluck('id')
        );
        }

        return $this->builder;
    }

    /**
     * Фильтр по Фамилии
     *
     * @param int $last_name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function last_name($last_name = 0)
    {
        if($last_name != 0){
            $last_name=Customer::distinct()->orderBy('last_name')->pluck('last_name')->prepend('--', 0)[$last_name];
            return $this->builder->whereIn(
                'id',
                Customer::where('last_name', $last_name)->pluck('id')
            );
            }
    
            return $this->builder;
       
    }

    /**
     * Фильтр по телефону
     *
     * @param int $phone
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function phone($phone = 0)
    {
        if($phone != 0){
            $phone=Customer::distinct()->orderBy('phone')->pluck('phone')->prepend('--', 0)[$phone];
            return $this->builder->whereIn(
                'id',
                Customer::where('phone', $phone)->pluck('id')
            );
            }
    
            return $this->builder;
    }

    /**
     * Фильтр по email
     *
     * @param int $email
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function email($email = 0)
    {
        if($email != 0){
            $email=Customer::distinct()->orderBy('email')->pluck('email')->prepend('--', 0)[$email];
            return $this->builder->whereIn(
                'id',
                Customer::where('email', $email)->pluck('id')
            );
            }
    
            return $this->builder;
    }
}
