<?php

namespace App\Http\Requests;

use App\ProductExchange;
use App\ProductReturn;
use Illuminate\Foundation\Http\FormRequest;

class CashboxOperationRequest extends FormRequest
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
            'currency_id' => 'required|exists:currencies,id',
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'required|in:C,D',
            'comment' => 'required|string|min:10',
            'order_id' => 'required|integer|min:0',
            'product_return_id' => 'required|integer|min:0',
            'product_exchange_id' => 'required|integer|min:0',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере


        if ($data['order_id'] == 0 && $data['product_return_id'] > 0) {
            $data['order_id'] = ProductReturn::find($data['product_return_id'])->order_id;
        }

        if ($data['order_id'] == 0 && $data['product_exchange_id'] > 0) {
            $data['order_id'] = ProductExchange::find($data['product_exchange_id'])->order_id;
        }


        $this->getInputSource()->add($data);

    }
}
