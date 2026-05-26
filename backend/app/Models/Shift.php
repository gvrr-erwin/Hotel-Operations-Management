<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'shift_type', 'start_time', 'end_time',
        'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getHoursAttribute(): float
    {
        [$sh, $sm] = explode(':', $this->start_time);
        [$eh, $em] = explode(':', $this->end_time);
        $startMin = (int) $sh * 60 + (int) $sm;
        $endMin   = (int) $eh * 60 + (int) $em;
        if ($endMin <= $startMin) {
            $endMin += 1440;
        }
        return round(($endMin - $startMin) / 60, 2);
    }
}
