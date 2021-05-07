<?php

namespace App\Http\Requests;

use Hash;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $user = $this->route()->parameter('user');

        return [
            'name' => 'required',
            'email' => [
                'required',
                'email',
                (is_object($user) ?
                    Rule::unique('users', 'email')->ignore($user->id)
                    :
                    Rule::unique(
                        'users',
                        'email'
                    )
                ),
            ],
            'password' => (is_object($user) ? 'same:confirm-password' : 'required|same:confirm-password'),
            'roles' => 'required',
            'color' => 'regex:/^#[0-9ABCDEFabcdef]{6}$/',
            'is_crm' => 'nullable'
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
            $this->getInputSource()->remove('password');
        }
        $this->getInputSource()->remove('telegram_chat_id');
        $data['is_crm'] = isset($data['is_crm']) ? $data['is_crm'] : 0;
        $this->getInputSource()->add($data);

    }
}
