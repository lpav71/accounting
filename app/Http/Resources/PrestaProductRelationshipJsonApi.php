<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PrestaProductRelationshipResourceJsonApi
 * @package App\Http\Resources
 * @mixin \App\PrestaProduct
 */
class PrestaProductRelationshipJsonApi extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'pictures' => new ProductPictureIdentifierCollectionJsonApi($this->product->pictures),
            'characteristics' => new CharacteristicIdentifierCollectionJsonApi($this->product->characteristics),
            'category' => new CategoryIdentifierJsonApi($this->product->category),
            'manufacturer' => new ManufacturerIdentifierJsonApi($this->product->manufacturer)
        ];
        if (!empty($this->product->combination) && $this->product->combination->hasChannelCombination($this->channel_id)) {
            $data['combination'] = new CombinationIdentifierJsonApi($this->product->combination);
            $data['attributes'] = new AttributeIdentifierCollectionJsonApi($this->product->attributes);
        }
        return $data;
    }
}
