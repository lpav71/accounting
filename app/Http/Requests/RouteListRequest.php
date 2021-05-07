<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RouteListRequest extends FormRequest
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
            'point_cashboxes' => 'array',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['point_cashboxes'] = isset($data['point_cashboxes']) ? $data['point_cashboxes'] : [];

        $this->getInputSource()->add($data);

    }
}
