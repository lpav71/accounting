<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
        $role = $this->route()->parameter('role');

        return [
            'name' => [
                'required',
                (is_object($role) ?
                    Rule::unique('roles', 'name')->ignore($role->id)
                    :
                    Rule::unique(
                        'roles',
                        'name'
                    )
                ),
            ],
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['permission'] = $data['permission'] ?? [];
        $data['is_courier'] = $data['is_courier'] ?? 0;
        $data['is_manager'] = $data['is_manager'] ?? 0;
        $data['is_task_performer'] = $data['is_task_performer'] ?? 0;
        $data['is_crm'] = $data['is_crm'] ?? 0;

        $this->getInputSource()->add($data);

    }
}
