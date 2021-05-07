<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 *
 * @mixin \App\ProductPicture
 *
 * Class ProductPictureJsonApi
 * @package App\Http\Resources
 */
class ProductPictureJsonApi extends JsonResource
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
            'type' => 'ProductPicture',
            'id' => (string)$this->id,
            'attributes' => [
                'url' => config('app.url') . $this->public_url .'?token='.config('product-picture.api_token'),
                'hash' => $this->getHash()
            ]
        ];
    }
}
