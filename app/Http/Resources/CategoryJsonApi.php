<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CategoryJsonApi
 * @package App\Http\Resources
 * @mixin \App\Category
 */
class CategoryJsonApi extends JsonResource
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
            'type' => 'Category',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name
            ],
            'relationships' => [

            ]
        ];
        if (!empty($this->parentCategory)) {
            $data['relationships']['parentCategory'] = new CategoryIdentifierJsonApi($this->parentCategory);
        }
        return $data;
    }
}
