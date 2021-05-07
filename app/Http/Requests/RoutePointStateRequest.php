<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoutePointStateRequest extends FormRequest
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
        $routePointState = $this->route()->parameter('route_point_state');

        return [
            'name' => [
                'required',
                (is_object($routePointState) ?
                    Rule::unique('route_point_states', 'name')->ignore($routePointState->id)
                    :
                    Rule::unique(
                        'route_point_states',
                        'name'
                    )
                ),
            ],
            'new_order_state_id' => 'required|integer|min:0',
            'new_product_return_state_id' => 'required|integer|min:0',
            'new_product_exchange_state_id' => 'required|integer|min:0',
            'is_detach_point_object' => 'integer|nullable',
            'is_attach_detached_point_object' => 'integer|nullable',
            'is_need_comment_to_point_object' => 'integer|nullable',
            'previous_states_id' => 'array',
            'color' => 'regex:/^#[0-9ABCDEFabcdef]{6}$/',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['previous_states_id'] = isset($data['previous_states_id']) ? $data['previous_states_id'] : [];
        $data['is_detach_point_object'] = isset($data['is_detach_point_object']) ? $data['is_detach_point_object'] : 0;
        $data['is_attach_detached_point_object'] = isset($data['is_attach_detached_point_object']) ? $data['is_attach_detached_point_object'] : 0;
        $data['is_need_comment_to_point_object'] = isset($data['is_need_comment_to_point_object']) ? $data['is_need_comment_to_point_object'] : 0;

        $this->getInputSource()->add($data);

    }
}
