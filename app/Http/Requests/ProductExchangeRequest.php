<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductExchangeRequest extends FormRequest
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
        $exchange = $this->route()->parameter('product_exchange');

        return [
            'order_id' => (is_object($exchange) ? 'integer' : 'required|integer'),
            'carrier_id' => 'integer|nullable',
            'comment' => 'string|nullable',
            'delivery_shipping_number' => 'string|nullable',
            'delivery_post_index' => 'string|nullable|size:6',
            'delivery_city' => 'string|nullable',
            'delivery_address' => 'string|nullable',
            'delivery_flat' => 'string|nullable',
            'delivery_comment' => 'string|nullable',
            'delivery_estimated_date' => 'nullable|regex:/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/',
            'delivery_start_time' => 'nullable|regex:/^[0-9]{2}:[0-9]{2}$/',
            'delivery_end_time' => 'nullable|regex:/^[0-9]{2}:[0-9]{2}$/',
            'order_detail' => 'array',
            'order_detail_add' => 'array',
            'order_detail_add.*.price' => 'required_with:order_detail_add|numeric|min:0',
            'order_detail_add.*.currency_id' => 'required_with:order_detail_add|integer|min:1',
            'order_detail_add.*.store_id' => 'required_with:order_detail_add|integer|min:1',
            'order_detail_add.*.order_detail_state_id' => 'required_with:order_detail_add|integer|min:1',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['carrier_id'] = isset($data['carrier_id']) ? $data['carrier_id'] : null;
        $data['order_detail'] = isset($data['order_detail']) ? $data['order_detail'] : [];
        $data['order_detail_add'] = isset($data['order_detail_add']) ? $data['order_detail_add'] : [];

        $this->getInputSource()->add($data);

    }
}
