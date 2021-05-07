<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
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
            'customer_id' => 'required|integer',
            'order_state_id' => 'required|integer',
            'channel_id' => 'required|integer',
            'user_id' => 'nullable|integer',
            'carrier_id' => 'nullable|integer',
            'date_estimated_delivery' => 'nullable|regex:/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/',
            'delivery_start_time' => 'nullable|regex:/^[0-9]{2}:[0-9]{2}$/',
            'delivery_end_time' => 'nullable|regex:/^[0-9]{2}:[0-9]{2}$/',
            'delivery_post_index' => 'nullable|string|size:6',
            'delivery_address_flat' => 'nullable|string|max:6',
            'order_detail' => 'array',
            'order_detail.*.price' => 'numeric|min:0',
            'order_detail.*.currency_id' => 'integer|min:1',
            'order_detail.*.store_id' => 'integer|min:1',
            'order_detail.*.order_detail_state_id' => 'integer|min:1',
            'order_detail.*.printing_group' => 'integer|min:0',
            'order_detail_add' => 'array',
            'order_detail_add.*.price' => 'required_with:order_detail_add|numeric|min:0',
            'order_detail_add.*.currency_id' => 'required_with:order_detail_add|integer|min:1',
            'order_detail_add.*.store_id' => 'required_with:order_detail_add|integer|min:1',
            'order_detail_add.*.order_detail_state_id' => 'required_with:order_detail_add|integer|min:1',
            'is_hidden' => 'integer|in:0,1|nullable',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['is_hidden'] = isset($data['is_hidden']) ? $data['is_hidden'] : 0;

        $this->getInputSource()->add($data);

    }
}
