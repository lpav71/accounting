<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UtmGroupRequest extends FormRequest
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
        $utmGroup = $this->route()->parameter('utm_group');

        return [
            'name' => [
                'required',
                (is_object($utmGroup) ?
                    Rule::unique('utm_groups', 'name')->ignore($utmGroup->id)
                    :
                    Rule::unique(
                        'utm_groups',
                        'name'
                    )
                ),
            ],
            'rule' => [
                'required',
                function ($attribute, $rules, $fail) {
                    try {
                        $values = explode('||', $rules);

                        foreach ($values as $value) {
                            if (preg_match($value, 'test') === false) {
                                $fail($attribute.' is not a regex.');
                            }
                        }

                    } catch (\ErrorException $e) {
                        $fail($attribute.' is not a regex.');
                    }

                },
            ],
            'indicator_clicks' => 'integer|nullable',
            'indicator_price_per_click_from' => 'numeric|min:0|nullable',
            'indicator_price_per_click_to' => 'numeric|min:0|nullable',
            'indicator_price_per_order_from' => 'numeric|min:0|nullable',
            'indicator_price_per_order_to' => 'numeric|min:0|nullable',
            'sort_order' => 'numeric|min:0|nullable',
        ];
    }
}
