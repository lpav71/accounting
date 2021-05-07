<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeJsonApi extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'type' => 'ProductAttribute',
            'id' => $this->pivot->id,
            'attributes' => [
                'name' => $this->name,
                'value' => $this->pivot->attr_value
            ]

        ];
    }
}
