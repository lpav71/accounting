<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class OperationFilterRequest extends FormRequest
{
    /**
     * Имеет ли пользователь право сделать такой запрос
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Правила проверки, которые применяются к запросу
     *
     * @return array
     */
    public function rules()
    {
        return [
            'operableId' => 'integer|min:1|nullable',
            'type' => 'string|nullable',
            'date' => 'date_format:Y-m-d|nullable'
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Мутация данных

        if (isset($data['type']) && $data['type'] === 'not') {
            $data['type'] = null;
        }

        if (isset($data['date']) && !is_null($data['date'])) {
            $data['date'] = Carbon::createFromFormat('Y-m-d', $data['date']);
        }


        $this->getInputSource()->add($data);

    }
}
