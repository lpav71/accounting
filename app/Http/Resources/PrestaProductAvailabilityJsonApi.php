<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrestaProductAvailabilityJsonApi extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'type' => 'PrestaProduct',
            'id' => (string)$this->product->id,
            'attributes' => [
                "available" => $this->is_active,
                "reference" => (string)$this->product->reference
            ]
        ];
    }
}
