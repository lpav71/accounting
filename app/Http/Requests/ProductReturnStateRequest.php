<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductReturnStateRequest extends FormRequest
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
        $productReturnState = $this->route()->parameter('product_return_state');

        return [
            'name' => [
                'required',
                (is_object($productReturnState) ?
                    Rule::unique('product_return_states', 'name')->ignore($productReturnState->id)
                    :
                    Rule::unique(
                        'product_return_states',
                        'name'
                    )
                ),
            ],
            'previous_states_id' => 'array',
            'need_order_detail_state_id' => 'array',
            'need_one_order_detail_state_id' => 'array',
            'new_order_detail_state_id' => 'required|integer|min:0',
            'check_payment' => 'integer|nullable',
            'color' => 'regex:/^#[0-9ABCDEFabcdef]{6}$/',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['previous_states_id'] = isset($data['previous_states_id']) ? $data['previous_states_id'] : [];
        $data['need_order_detail_state_id'] = isset($data['need_order_detail_state_id']) ? $data['need_order_detail_state_id'] : [];
        $data['need_one_order_detail_state_id'] = isset($data['need_one_order_detail_state_id']) ? $data['need_one_order_detail_state_id'] : [];
        $data['check_payment'] = isset($data['check_payment']) ? $data['check_payment'] : 0;

        $this->getInputSource()->add($data);

    }
}
