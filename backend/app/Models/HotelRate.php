<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id', 'room_type_id', 'date', 'rate', 'notes', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'rate' => 'decimal:2',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
