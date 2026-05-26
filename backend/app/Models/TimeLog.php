<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'clock_in', 'clock_out', 'shift_type',
        'status', 'notes', 'logged_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date:Y-m-d',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getHoursWorkedAttribute(): ?float
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return null;
        }

        [$ih, $im] = explode(':', $this->clock_in);
        [$oh, $om] = explode(':', $this->clock_out);

        $inMinutes  = (int) $ih * 60 + (int) $im;
        $outMinutes = (int) $oh * 60 + (int) $om;

        if ($outMinutes < $inMinutes) {
            $outMinutes += 1440; // overnight shift
        }

        return round(($outMinutes - $inMinutes) / 60, 2);
    }
}
