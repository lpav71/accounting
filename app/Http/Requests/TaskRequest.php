<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string',
            'task_state_id' => 'required|integer',
            'customer_id' => 'nullable|integer',
            'order_id' => 'nullable|integer',
            'performer_user_id' => 'nullable|integer',
            'task_priority_id' => 'required|integer',
        ];
    }
}
