<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'user'       => $this->whenLoaded('user', fn () => [
                'id'         => $this->user->id,
                'name'       => $this->user->name,
                'role'       => $this->user->role,
                'department' => $this->user->department,
            ]),
            'date'       => $this->date?->format('Y-m-d'),
            'shift_type' => $this->shift_type,
            'start_time' => $this->start_time,
            'end_time'   => $this->end_time,
            'hours'      => $this->hours,
            'status'     => $this->status,
            'notes'      => $this->notes,
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
