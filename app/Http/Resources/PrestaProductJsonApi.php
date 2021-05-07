<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PrestaProductJsonApi
 * @package App\Http\Resources
 * @mixin \App\PrestaProduct
 */
class PrestaProductJsonApi extends JsonResource
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
            'type' => 'PrestaProduct',
            'id' => (string)$this->product->id,
            'attributes' => [
                'name' => $this->product->title,
                "reference" => (string)$this->product->reference,
                "price" => (string)$this->price,
                "price_discount" => (string)$this->price_discount,
                "barcode" => (string)$this->product->ean,
                "description" => (string)$this->description,
                "available" => (string)$this->is_active,
                "raiting" => (string)$this->rating,
            ],
            'relationships' => new PrestaProductRelationshipJsonApi($this->resource)
        ];
    }

}
