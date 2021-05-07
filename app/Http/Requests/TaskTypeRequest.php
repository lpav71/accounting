<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидатор запроса обновления/создания типа задачи
 *
 * @author Vladimir Tikunov <vtikunov@yandex.ru>
 */
class TaskTypeRequest extends FormRequest
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
        $taskType = $this->route()->parameter('task_type');

        return [
            'name' => [
                'required',
                'string',
                (is_object($taskType) ? Rule::unique('task_types', 'name')->ignore($taskType->id) : Rule::unique(
                    'task_types',
                    'name'
                )),
            ],
            'color' => 'regex:/^#[0-9ABCDEFabcdef]{6}$/',
            'priority_users' => 'array|nullable',
            'priority_roles' => 'array|nullable',
            'disabled_users' => 'array|nullable'
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['priority_users'] = $data['priority_users'] ?? [];
        $data['priority_users'] = array_map('intval', $data['priority_users']);

        $data['priority_roles'] = $data['priority_roles'] ?? [];
        $data['priority_roles'] = array_map('intval', $data['priority_roles']);

        $data['disabled_users'] = $data['disabled_users'] ?? [];
        $data['disabled_users'] = array_map('intval', $data['disabled_users']);

        $this->getInputSource()->add($data);
    }
}
