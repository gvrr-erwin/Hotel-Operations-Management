<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function log(
        string $action,
        string $description,
        ?int $userId = null,
        ?Model $auditable = null,
        ?array $metadata = null,
        ?string $ipAddress = null
    ): AuditLog {
        return AuditLog::create([
            'user_id'        => $userId,
            'action'         => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id'   => $auditable?->getKey(),
            'description'    => $description,
            'ip_address'     => $ipAddress,
            'metadata'       => $metadata,
        ]);
    }

    public function logFromRequest(
        Request $request,
        string $action,
        string $description,
        ?Model $auditable = null,
        ?array $metadata = null
    ): AuditLog {
        return $this->log(
            action: $action,
            description: $description,
            userId: $request->user()?->id,
            auditable: $auditable,
            metadata: $metadata,
            ipAddress: $request->ip(),
        );
    }
}
