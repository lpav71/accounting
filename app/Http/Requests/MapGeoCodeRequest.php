<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MapGeoCodeRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'geoX' => 'numeric|required',
            'geoY' => 'numeric|required',
        ];
    }
}
