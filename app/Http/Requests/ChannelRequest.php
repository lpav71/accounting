<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидатор запроса для источника заказа
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class ChannelRequest extends FormRequest
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
        $channel = $this->route()->parameter('channel');

        return [
            'name' => [
                'required',
                'string',
                (is_object($channel) ? Rule::unique('channels', 'name')->ignore($channel->id) : Rule::unique(
                    'channels',
                    'name'
                )),
            ],
            'notifications_email' => 'email|nullable',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['url'] = $data['url'] ?? '';
        $data['smtp_is_enabled'] = $data['smtp_is_enabled'] ?? 0;
        $data['sms_is_enabled'] = $data['sms_is_enabled'] ?? 0;
        $data['is_landscape_docs'] = $data['is_landscape_docs'] ?? 0;
        $data['is_hidden'] = $data['is_hidden'] ?? 0;

        $this->getInputSource()->add($data);

    }
}
