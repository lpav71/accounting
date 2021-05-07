<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RouteListManageRequest extends FormRequest
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
        return [
            'date' => 'required|regex:/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/',
            'orders_route_lists' => 'array',
            'product_returns_route_lists' => 'array',
            'product_exchanges_route_lists' => 'array',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['orders_route_lists'] = isset($data['orders_route_lists']) ? $data['orders_route_lists'] : [];
        $data['product_returns_route_lists'] = isset($data['product_returns_route_lists']) ? $data['product_returns_route_lists'] : [];
        $data['product_exchanges_route_lists'] = isset($data['product_exchanges_route_lists']) ? $data['product_exchanges_route_lists'] : [];

        $this->getInputSource()->add($data);

    }
}
