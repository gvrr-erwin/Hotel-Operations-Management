<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'user'           => $this->whenLoaded('user', fn () => $this->user ? [
                'id'   => $this->user->id,
                'name' => $this->user->name,
                'role' => $this->user->role,
            ] : null),
            'action'         => $this->action,
            'auditable_type' => $this->auditable_type,
            'auditable_id'   => $this->auditable_id,
            'description'    => $this->description,
            'ip_address'     => $this->ip_address,
            'metadata'       => $this->metadata,
            'created_at'     => $this->created_at->toIso8601String(),
        ];
    }
}
