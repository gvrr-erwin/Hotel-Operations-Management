<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'hotel_id'     => $this->hotel_id,
            'hotel'        => $this->whenLoaded('hotel', fn () => [
                'id'   => $this->hotel->id,
                'name' => $this->hotel->name,
                'code' => $this->hotel->code,
            ]),
            'room_type_id' => $this->room_type_id,
            'room_type'    => $this->whenLoaded('roomType', fn () => [
                'id'   => $this->roomType->id,
                'name' => $this->roomType->name,
                'code' => $this->roomType->code,
            ]),
            'date'         => $this->date?->format('Y-m-d'),
            'rate'         => (float) $this->rate,
            'notes'        => $this->notes,
            'recorded_by'  => $this->whenLoaded('recorder', fn () => [
                'id'   => $this->recorder->id,
                'name' => $this->recorder->name,
            ]),
            'created_at'   => $this->created_at->toIso8601String(),
            'updated_at'   => $this->updated_at->toIso8601String(),
        ];
    }
}
