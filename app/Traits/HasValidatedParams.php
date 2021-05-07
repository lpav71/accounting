<?php

namespace App\Traits;

use App\Modules\ModelValidator;

/**
 * Трэйт HasValidatedParams
 */
trait HasValidatedParams
{
    /**
     * Вызывает экземпляр Валидатора для валидации.
     */
    public function validate()
    {
        // Валидация модели
        (new ModelValidator($this, $this->validatedParams ?: []))->validate();
    }

    /**
     * Метод-псевдоним для вызова метода validate при сохранении модели
     */
    public static function validateOnSaving()
    {
        static::saving(
            function ($model) {
                /**
                 * @var $model \App\Traits\HasValidatedParams
                 */
                $model->validate();
            }
        );
    }

    /**
     * Метод-псевдоним для вызова метода validate при создании модели
     */
    public static function validateOnCreating()
    {
        static::creating(
            function ($model) {
                /**
                 * @var $model \App\Traits\HasValidatedParams
                 */
                $model->validate();
            }
        );
    }
}