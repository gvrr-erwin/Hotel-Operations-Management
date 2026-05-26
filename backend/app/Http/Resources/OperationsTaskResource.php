<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationsTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'category'     => $this->category,
            'priority'     => $this->priority,
            'status'       => $this->status,
            'room_number'  => $this->room_number,
            'assignee'     => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id'         => $this->assignee->id,
                'name'       => $this->assignee->name,
                'role'       => $this->assignee->role,
                'department' => $this->assignee->department,
            ] : null),
            'creator'      => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'due_at'       => $this->due_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_overdue'   => $this->is_overdue,
            'created_at'   => $this->created_at->toIso8601String(),
        ];
    }
}
