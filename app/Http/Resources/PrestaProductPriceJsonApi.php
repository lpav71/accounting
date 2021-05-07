<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrestaProductPriceJsonApi extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $data = [
            'type' => 'PrestaProduct',
            'id' => (string)$this->product->id,
            'attributes' => [
                "price" => (string)$this->price,
                "price_discount" => (string)$this->price_discount,
                "reference" => (string)$this->product->reference,
            ],
            'relationships' => [
            ]
        ];
        if (!empty($this->product->combination) && $this->product->combination->hasChannelCombination($this->channel_id)) {
            $data['relationships']['combination'] = new CombinationIdentifierJsonApi($this->product->combination);
        }

        return $data;
    }
}
