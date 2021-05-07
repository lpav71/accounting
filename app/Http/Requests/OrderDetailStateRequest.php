<?php

namespace App\Http\Requests;

use App\OrderDetail;
use App\OrderDetailState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderDetailStateRequest extends FormRequest
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
        $orderDetailState = $this->route()->parameter('order_detail_state');

        return [
            'name' => 'required',
            'full_name' => [
                'required',
                (is_object($orderDetailState) ?
                    Rule::unique('order_detail_states', 'full_name')->ignore($orderDetailState->id)
                    :
                    Rule::unique(
                        'order_detail_states',
                        'full_name'
                    )
                ),
            ],
            'is_courier_state' => 'integer|nullable',
            'previous_order_detail_states_id' => 'array',
            'is_hidden' => 'required|integer|in:0,1',
            'store_operation' => 'required|in:not,C,D,CR,DR',
            'is_block_editing_order_detail' => 'integer|nullable',
            'is_block_deleting_order_detail' => 'integer|nullable',
            'is_block_editing_store' => 'integer|nullable',
            'currency_operation_by_order' => 'required|in:not,C,D',
            'product_operation_by_order' => 'required|in:not,C,D',
            'owner_type' => [
                'required',
                Rule::in(array_keys(OrderDetailState::OWNERS)),
            ],
            'new_order_detail_owner_type' => [
                'required',
                Rule::in(array_merge(['not'], array_keys(OrderDetailState::OWNERS))),
            ],
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['is_courier_state'] = isset($data['is_courier_state']) ? $data['is_courier_state'] : 0;
        $data['is_block_editing_order_detail'] = isset($data['is_block_editing_order_detail']) ? $data['is_block_editing_order_detail'] : 0;
        $data['is_block_deleting_order_detail'] = isset($data['is_block_deleting_order_detail']) ? $data['is_block_deleting_order_detail'] : 0;
        $data['is_block_editing_store'] = isset($data['is_block_editing_store']) ? $data['is_block_editing_store'] : 0;
        $data['is_new'] = isset($data['is_new']) ? $data['is_new'] : 0;
        $data['new_order_detail_owner_type'] = $data['new_order_detail_owner_type'] !== 'not' ? $data['new_order_detail_owner_type'] : null;

        $this->getInputSource()->add($data);

    }
}
