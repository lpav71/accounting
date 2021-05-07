<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CharacteristicJsonApi
 * @package App\Http\Resources
 */
class CharacteristicJsonApi extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'type' => 'ProductCharacteristic',
            'id' => (int)$this->pivot->id,
            'attributes' => [
                'name'=>$this->name,
                'value'=>$this->pivot->attr_value
            ]
        ];
    }
}
