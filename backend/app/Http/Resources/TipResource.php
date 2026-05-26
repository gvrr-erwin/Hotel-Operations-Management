<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'employee_id' => $this->employee_id,
            'employee'    => $this->whenLoaded('employee', fn () => [
                'id'   => $this->employee->id,
                'name' => $this->employee->name,
                'role' => $this->employee->role,
            ]),
            'amount'      => (float) $this->amount,
            'date'        => $this->date?->format('Y-m-d'),
            'note'        => $this->note,
            'recorded_by' => $this->whenLoaded('recorder', fn () => [
                'id'   => $this->recorder->id,
                'name' => $this->recorder->name,
            ]),
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
