<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessage extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'text' => $this->text,
            'created_at' => (new Carbon($this->created_at))->toDateTimeString(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name
            ]

        ];
    }
}
