<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskPriorityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $taskPriority = $this->route()->parameter('task_priority');

        return [
            'name' => [
                'required',
                'string',
                (is_object($taskPriority) ? Rule::unique('task_priorities', 'name')->ignore($taskPriority->id) : Rule::unique(
                    'task_priorities',
                    'name'
                )),
            ],
            'rate' => 'integer',
        ];
    }
}
