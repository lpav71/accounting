<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $taskState = $this->route()->parameter('task_state');

        return [
            'name' => [
                'required',
                (is_object($taskState) ? Rule::unique('task_states', 'name')->ignore($taskState->id) : Rule::unique(
                    'task_states',
                    'name'
                )),
            ],
            'previous_task_states_id' => 'array',
            'color' => 'regex:/^#[0-9ABCDEFabcdef]{6}$/',
        ];
    }
}
