<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHotelRateRequest;
use App\Http\Resources\HotelRateResource;
use App\Models\Hotel;
use App\Models\HotelRate;
use App\Models\RoomType;
use App\Services\AuditService;
use App\Services\RateAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HotelRateController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private RateAnalyticsService $analytics,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = HotelRate::with(['hotel', 'roomType', 'recorder'])
            ->orderByDesc('date')
            ->orderBy('hotel_id');

        if ($request->date) {
            $query->whereDate('date', $request->date);
        }

        if ($request->date_from && $request->date_to) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        if ($request->hotel_id) {
            $query->where('hotel_id', $request->hotel_id);
        }

        $rates = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'data' => HotelRateResource::collection($rates->items()),
            'meta' => [
                'total'        => $rates->total(),
                'current_page' => $rates->currentPage(),
                'last_page'    => $rates->lastPage(),
                'per_page'     => $rates->perPage(),
            ],
        ]);
    }

    public function store(StoreHotelRateRequest $request): JsonResponse
    {
        $rate = HotelRate::updateOrCreate(
            [
                'hotel_id'     => $request->hotel_id,
                'room_type_id' => $request->room_type_id,
                'date'         => $request->date,
            ],
            [
                'rate'        => $request->rate,
                'notes'       => $request->notes,
                'recorded_by' => $request->user()->id,
            ]
        );

        $rate->load(['hotel', 'roomType', 'recorder']);

        $this->audit->logFromRequest(
            $request,
            'rate_saved',
            "Rate saved: {$rate->hotel->name} / {$rate->roomType->name} = \${$rate->rate} on {$rate->date->format('Y-m-d')}",
            $rate
        );

        return response()->json(['data' => new HotelRateResource($rate)], 201);
    }

    public function update(StoreHotelRateRequest $request, HotelRate $rate): JsonResponse
    {
        $rate->update([
            'rate'        => $request->rate,
            'notes'       => $request->notes,
            'recorded_by' => $request->user()->id,
        ]);

        $rate->load(['hotel', 'roomType', 'recorder']);

        $this->audit->logFromRequest(
            $request,
            'rate_updated',
            "Rate updated: {$rate->hotel->name} / {$rate->roomType->name} = \${$rate->rate}",
            $rate
        );

        return response()->json(['data' => new HotelRateResource($rate)]);
    }

    public function destroy(Request $request, HotelRate $rate): JsonResponse
    {
        $rate->load(['hotel', 'roomType']);
        $this->audit->logFromRequest($request, 'rate_deleted', "Rate deleted for {$rate->hotel->name} on {$rate->date->format('Y-m-d')}");
        $rate->delete();

        return response()->json(['message' => 'Rate deleted.']);
    }

    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'date'         => ['required', 'date_format:Y-m-d'],
            'compare_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return response()->json($this->analytics->compareByDate($request->date, $request->compare_date));
    }

    public function historical(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to'   => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return response()->json([
            'data' => $this->analytics->getHistoricalSummary($request->from, $request->to),
        ]);
    }

    public function hotels(): JsonResponse
    {
        return response()->json([
            'data' => Hotel::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function roomTypes(): JsonResponse
    {
        return response()->json([
            'data' => RoomType::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }
}
