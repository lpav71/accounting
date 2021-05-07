<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидатор запроса для Http Fast Test + Http Test
 *
 * @package App\Http\Requests
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class HttpFastTestRequest extends FormRequest
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
            'name' => 'required',
            'is_active' => 'boolean|nullable',
            'is_message' => 'boolean|nullable',
            'url' => 'url|required',
            'period' => 'integer|min:1|required',
            'need_string_in_body' => 'string|required',
            'need_response_time' => 'integer|min:1|required',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data = array_merge(
            $data,
            [
                'is_active' => $data['is_active'] ?? 0,
                'is_message' => $data['is_message'] ?? 0,
            ]
        );

        $this->getInputSource()->add($data);
    }
}
