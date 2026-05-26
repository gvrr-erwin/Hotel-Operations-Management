<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Shift::with(['user', 'creator'])
            ->orderBy('date')
            ->orderBy('start_time');

        // Non-managers see only their own shifts
        if (! $user->isManagement()) {
            $query->where('user_id', $user->id);
        }

        if ($request->user_id && $user->isManagement()) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->department && $user->isManagement()) {
            $query->whereHas('user', fn ($q) => $q->where('department', $request->department));
        }

        if ($request->date_from && $request->date_to) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        } elseif ($request->date) {
            $query->whereDate('date', $request->date);
        } else {
            // default: current + next week
            $query->whereBetween('date', [
                Carbon::now()->startOfWeek()->toDateString(),
                Carbon::now()->endOfWeek()->addWeek()->toDateString(),
            ]);
        }

        return response()->json([
            'data' => ShiftResource::collection($query->get()),
        ]);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        if ($conflict = $this->findConflict($request->user_id, $request->date, $request->start_time, $request->end_time)) {
            return response()->json([
                'message' => 'Shift conflicts with an existing scheduled shift for this employee.',
                'conflict' => [
                    'id'         => $conflict->id,
                    'shift_type' => $conflict->shift_type,
                    'start_time' => $conflict->start_time,
                    'end_time'   => $conflict->end_time,
                ],
            ], 422);
        }

        $shift = Shift::create([
            'user_id'    => $request->user_id,
            'date'       => $request->date,
            'shift_type' => $request->shift_type,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
            'status'     => $request->status ?? 'published',
            'notes'      => $request->notes,
            'created_by' => $request->user()->id,
        ]);

        $shift->load(['user', 'creator']);
        $this->audit->logFromRequest(
            $request, 'shift_created',
            "Scheduled {$shift->user->name} for {$shift->shift_type} on {$shift->date->format('Y-m-d')}",
            $shift
        );

        return response()->json(['data' => new ShiftResource($shift)], 201);
    }

    public function update(StoreShiftRequest $request, Shift $shift): JsonResponse
    {
        if ($conflict = $this->findConflict($request->user_id, $request->date, $request->start_time, $request->end_time, $shift->id)) {
            return response()->json([
                'message' => 'Shift conflicts with an existing scheduled shift for this employee.',
            ], 422);
        }

        $shift->update($request->only([
            'user_id', 'date', 'shift_type', 'start_time', 'end_time', 'status', 'notes',
        ]));

        $shift->load(['user', 'creator']);
        $this->audit->logFromRequest($request, 'shift_updated', "Shift #{$shift->id} updated", $shift);

        return response()->json(['data' => new ShiftResource($shift)]);
    }

    public function destroy(Request $request, Shift $shift): JsonResponse
    {
        $this->audit->logFromRequest($request, 'shift_deleted', "Shift #{$shift->id} deleted");
        $shift->delete();

        return response()->json(['message' => 'Shift removed.']);
    }

    private function findConflict(int $userId, string $date, string $start, string $end, ?int $excludeId = null): ?Shift
    {
        $query = Shift::where('user_id', $userId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get()->first(function (Shift $existing) use ($start, $end) {
            return $this->overlaps($existing->start_time, $existing->end_time, $start, $end);
        });
    }

    private function overlaps(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        $toMin = fn (string $t) => (int) explode(':', $t)[0] * 60 + (int) explode(':', $t)[1];
        $a1 = $toMin($aStart);
        $a2 = $toMin($aEnd);
        $b1 = $toMin($bStart);
        $b2 = $toMin($bEnd);
        if ($a2 <= $a1) $a2 += 1440;
        if ($b2 <= $b1) $b2 += 1440;
        return $a1 < $b2 && $b1 < $a2;
    }
}
