<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashboxRequest extends FormRequest
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
        $cashbox = $this->route()->parameter('cashbox');

        return [
            'name' => [
                'required',
                (is_object($cashbox) ?
                    Rule::unique('cashboxes', 'name')->ignore($cashbox->id)
                    :
                    Rule::unique('cashboxes', 'name')
                ),
            ],
            'user_id' => 'array',
            'is_non_cash' => 'integer|nullable',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['user_id'] = isset($data['user_id']) ? $data['user_id'] : [];
        $data['is_non_cash'] = isset($data['is_non_cash']) ? $data['is_non_cash'] : 0;

        $this->getInputSource()->add($data);

    }
}
