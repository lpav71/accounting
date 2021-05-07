<?php

namespace App\Http\Resources;

use App\Combination;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CombinationJsonApi
 * @package App\Http\Resources
 * @mixin Combination
 */
class CombinationJsonApi extends JsonResource
{
    private $channel_id;

    public function __construct($resource, $channel_id)
    {
        $this->channel_id = $channel_id;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $products = $this->productsChannel($this->channel_id)->sortBy('reference');
        return [
            'type' => 'Combination',
            'id' => (string)$this->id,
            'relationships' => [
                'products' => new PrestaProductIdentifierCollectionJsonApi($products)
            ]
        ];
    }
}
