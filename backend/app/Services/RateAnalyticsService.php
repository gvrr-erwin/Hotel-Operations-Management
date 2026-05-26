<?php

namespace App\Services;

use App\Models\HotelRate;
use Carbon\Carbon;

class RateAnalyticsService
{
    public function compareByDate(string $date, ?string $compareDate = null): array
    {
        $compareDate = $compareDate ?? Carbon::parse($date)->subDay()->toDateString();

        $current  = $this->getRatesMatrix($date);
        $previous = $this->getRatesMatrix($compareDate);

        $diff = [];
        foreach ($current as $key => $entry) {
            $prevRate = $previous[$key]['rate'] ?? null;
            $diff[$key] = array_merge($entry, [
                'prev_rate' => $prevRate,
                'change'    => $prevRate !== null ? round($entry['rate'] - $prevRate, 2) : null,
                'change_pct'=> $prevRate > 0
                    ? round((($entry['rate'] - $prevRate) / $prevRate) * 100, 1)
                    : null,
            ]);
        }

        return [
            'date'         => $date,
            'compare_date' => $compareDate,
            'rates'        => array_values($diff),
        ];
    }

    public function getHistoricalSummary(string $from, string $to): array
    {
        return HotelRate::selectRaw('date, hotel_id, AVG(rate) as avg_rate, MIN(rate) as min_rate, MAX(rate) as max_rate, COUNT(*) as room_count')
            ->with('hotel:id,name,code')
            ->whereBetween('date', [$from, $to])
            ->groupBy('date', 'hotel_id')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date'       => $r->date,
                'hotel'      => $r->hotel ? ['id' => $r->hotel->id, 'name' => $r->hotel->name] : null,
                'avg_rate'   => round($r->avg_rate, 2),
                'min_rate'   => round($r->min_rate, 2),
                'max_rate'   => round($r->max_rate, 2),
                'room_count' => $r->room_count,
            ])
            ->toArray();
    }

    private function getRatesMatrix(string $date): array
    {
        $rates = HotelRate::with(['hotel:id,name,code', 'roomType:id,name,code'])
            ->whereDate('date', $date)
            ->get();

        $matrix = [];
        foreach ($rates as $rate) {
            $key = "{$rate->hotel_id}_{$rate->room_type_id}";
            $matrix[$key] = [
                'hotel'     => ['id' => $rate->hotel->id, 'name' => $rate->hotel->name],
                'room_type' => ['id' => $rate->roomType->id, 'name' => $rate->roomType->name],
                'rate'      => (float) $rate->rate,
            ];
        }

        return $matrix;
    }
}
