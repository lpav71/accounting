<?php

namespace App\Modules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class ModelValidator
{
    /**
     * Объект-модель для Валидации
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Параметры модели для валидации
     *
     * @var array
     */
    protected $validatedParams = [];

    /**
     * Конструктор ModelValidator
     *
     * @param $model \Illuminate\Database\Eloquent\Model
     * @param $validatedParams array
     */
    public function __construct(Model $model, Array $validatedParams)
    {
        $this->model = $model;

        $this->validatedParams = $validatedParams;
    }

    /**
     * Валидация параметров модели
     *
     * @return $this
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate()
    {
        if ($this->model->validatedParams) {
            $simpleParams = [];
            foreach ($this->model->validatedParams as $paramName => $validatedParam) {
                if (is_array($validatedParam)) {
                    Validator::make($this->model->$paramName, $validatedParam)->validate();
                } else {
                    $simpleParams[$paramName] = $validatedParam;
                }
            }
            Validator::make($this->model->toArray(), $simpleParams)->validate();
        }

        return $this;
    }
}