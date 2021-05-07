<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RouteListStateRequest extends FormRequest
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
        $routeListState = $this->route()->parameter('route_list_state');

        return [
            'name' => [
                'required',
                (is_object($routeListState) ?
                    Rule::unique('route_list_states', 'name')->ignore($routeListState->id)
                    :
                    Rule::unique(
                        'route_list_states',
                        'name'
                    )
                ),
            ],
            'is_editable_route_list' => 'integer|nullable',
            'is_deletable_route_points' => 'integer|nullable',
            'is_create_currency_operations' => 'integer|nullable',
            'color' => 'regex:/^#[0-9ABCDEFabcdef]{6}$/',
            'previous_states_id' => 'array',
            'need_order_state_id' => 'array',
            'need_product_return_state_id' => 'array',
            'need_product_exchange_state_id' => 'array',
            'need_order_detail_state_id' => 'array',
            'new_order_states' => 'array',
            'new_product_return_states' => 'array',
            'new_product_exchange_states' => 'array',
            'new_order_detail_states' => 'array',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['is_editable_route_list'] = isset($data['is_editable_route_list']) ? $data['is_editable_route_list'] : 0;
        $data['is_deletable_route_points'] = isset($data['is_deletable_route_points']) ? $data['is_deletable_route_points'] : 0;
        $data['is_create_currency_operations'] = isset($data['is_create_currency_operations']) ? $data['is_create_currency_operations'] : 0;
        $data['previous_states_id'] = isset($data['previous_states_id']) ? $data['previous_states_id'] : [];
        $data['need_order_state_id'] = isset($data['need_order_state_id']) ? $data['need_order_state_id'] : [];
        $data['need_product_return_state_id'] = isset($data['need_product_return_state_id']) ? $data['need_product_return_state_id'] : [];
        $data['need_product_exchange_state_id'] = isset($data['need_product_exchange_state_id']) ? $data['need_product_exchange_state_id'] : [];
        $data['need_order_detail_state_id'] = isset($data['need_order_detail_state_id']) ? $data['need_order_detail_state_id'] : [];
        $data['new_order_states'] = isset($data['new_order_states']) ? $data['new_order_states'] : [];
        $data['new_product_return_states'] = isset($data['new_product_return_states']) ? $data['new_product_return_states'] : [];
        $data['new_product_exchange_states'] = isset($data['new_product_exchange_states']) ? $data['new_product_exchange_states'] : [];
        $data['new_order_detail_states'] = isset($data['new_order_detail_states']) ? $data['new_order_detail_states'] : [];

        $this->getInputSource()->add($data);
    }
}
