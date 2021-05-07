<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductReturnRequest extends FormRequest
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
        $return = $this->route()->parameter('product_return');

        return [
            'order_id' => (is_object($return) ? 'integer' : 'required|integer'),
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
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['carrier_id'] = isset($data['carrier_id']) ? $data['carrier_id'] : null;
        $data['order_detail'] = isset($data['order_detail']) ? $data['order_detail'] : [];

        $this->getInputSource()->add($data);

    }
}
