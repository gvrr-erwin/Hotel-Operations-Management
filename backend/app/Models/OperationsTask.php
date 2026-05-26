<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationsTask extends Model
{
    use HasFactory;

    protected $table = 'operations_tasks';

    protected $fillable = [
        'title', 'description', 'category', 'priority', 'status',
        'room_number', 'assigned_to', 'created_by', 'due_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at'       => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at
            && $this->due_at->isPast()
            && ! in_array($this->status, ['completed', 'cancelled']);
    }
}
