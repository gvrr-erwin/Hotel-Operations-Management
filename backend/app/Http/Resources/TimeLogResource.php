<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'user'         => $this->whenLoaded('user', fn () => [
                'id'         => $this->user->id,
                'name'       => $this->user->name,
                'role'       => $this->user->role,
                'department' => $this->user->department,
            ]),
            'date'         => $this->date?->format('Y-m-d'),
            'clock_in'     => $this->clock_in,
            'clock_out'    => $this->clock_out,
            'shift_type'   => $this->shift_type,
            'status'       => $this->status,
            'hours_worked' => $this->hours_worked,
            'notes'        => $this->notes,
            'logged_by'    => $this->whenLoaded('logger', fn () => $this->logger ? [
                'id'   => $this->logger->id,
                'name' => $this->logger->name,
            ] : null),
            'approved_by'  => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id'   => $this->approver->id,
                'name' => $this->approver->name,
            ] : null),
            'approved_at'  => $this->approved_at?->toIso8601String(),
            'is_self'      => $this->user_id === $this->logged_by,
            'created_at'   => $this->created_at->toIso8601String(),
        ];
    }
}
