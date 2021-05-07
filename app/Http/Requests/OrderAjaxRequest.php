<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderAjaxRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customerFirstName' => 'nullable|string',
            'customerLastName' => 'nullable|string',
            'customerEmail' => 'required_without:customerPhone|email',
            'customerPhone' => 'required_without:customerEmail|regex:/^[\+]{0,1}[0-9\-\(\)\s]+$/',
            'orderState' => 'required|string|exists:order_states,name',
            'channel' => 'required|string|exists:channels,name',
            'deliveryAddressComment' => 'nullable|string',
            'orderComment' => '',
            'products' => 'array',
            'products.*.reference' => 'required_with:products|string',
            'products.*.price' => 'required_with:products|numeric|min:0',
            'products.*.currencyName' => 'required_with:products|string|exists:currencies,name',
            'products.*.state' => 'required_with:products|string|exists:order_detail_states,name',
        ];
    }
}
