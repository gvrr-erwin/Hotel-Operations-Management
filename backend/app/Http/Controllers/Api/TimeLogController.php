<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTimeLogRequest;
use App\Http\Resources\TimeLogResource;
use App\Models\TimeLog;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeLogController extends Controller
{
    public function __construct(private AuditService $audit) {}

    /** Managers see all logs; staff see only their own. */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = TimeLog::with(['user', 'logger', 'approver'])
            ->orderByDesc('date')
            ->orderByDesc('clock_in');

        if (! $user->isManagement()) {
            $query->where('user_id', $user->id);
        }

        if ($request->user_id && $user->isManagement()) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->department && $user->isManagement()) {
            $query->whereHas('user', fn ($q) => $q->where('department', $request->department));
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date) {
            $query->whereDate('date', $request->date);
        }

        if ($request->date_from && $request->date_to) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        if ($request->shift_type) {
            $query->where('shift_type', $request->shift_type);
        }

        $logs = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'data' => TimeLogResource::collection($logs->items()),
            'meta' => [
                'total'        => $logs->total(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
            ],
        ]);
    }

    /** Authenticated user's currently open session, if any. */
    public function active(Request $request): JsonResponse
    {
        $log = TimeLog::with(['user', 'logger'])
            ->where('user_id', $request->user()->id)
            ->whereNull('clock_out')
            ->orderByDesc('date')
            ->orderByDesc('clock_in')
            ->first();

        return response()->json([
            'data' => $log ? new TimeLogResource($log) : null,
        ]);
    }

    /** Self clock-in for any authenticated user. */
    public function clockIn(Request $request): JsonResponse
    {
        $user = $request->user();

        $open = TimeLog::where('user_id', $user->id)->whereNull('clock_out')->first();
        if ($open) {
            return response()->json([
                'message' => 'You are already clocked in. Please clock out first.',
            ], 422);
        }

        $now  = Carbon::now();
        $hour = (int) $now->format('H');
        $shift = match (true) {
            $hour >= 5  && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 17 => 'afternoon',
            $hour >= 17 && $hour < 22 => 'evening',
            default                   => 'night',
        };

        $log = TimeLog::create([
            'user_id'    => $user->id,
            'date'       => $now->toDateString(),
            'clock_in'   => $now->format('H:i'),
            'clock_out'  => null,
            'shift_type' => $request->shift_type ?? $shift,
            'status'     => 'pending',
            'notes'      => $request->notes,
            'logged_by'  => $user->id,
        ]);

        $log->load(['user', 'logger']);

        $this->audit->logFromRequest(
            $request, 'time_clock_in',
            "{$user->name} clocked in at {$log->clock_in}",
            $log
        );

        return response()->json(['data' => new TimeLogResource($log)], 201);
    }

    /** Self clock-out for any authenticated user. */
    public function clockOut(Request $request): JsonResponse
    {
        $user = $request->user();

        $log = TimeLog::where('user_id', $user->id)->whereNull('clock_out')->first();
        if (! $log) {
            return response()->json(['message' => 'No active session to clock out from.'], 422);
        }

        $log->update([
            'clock_out' => Carbon::now()->format('H:i'),
            'notes'     => $request->notes ?? $log->notes,
        ]);

        $log->load(['user', 'logger']);

        $this->audit->logFromRequest(
            $request, 'time_clock_out',
            "{$user->name} clocked out at {$log->clock_out} ({$log->hours_worked}h worked)",
            $log
        );

        return response()->json(['data' => new TimeLogResource($log)]);
    }

    /** Manager: create a correction or back-log entry (auto-approved). */
    public function store(StoreTimeLogRequest $request): JsonResponse
    {
        $log = TimeLog::create([
            'user_id'    => $request->user_id,
            'date'       => $request->date,
            'clock_in'   => $request->clock_in,
            'clock_out'  => $request->clock_out,
            'shift_type' => $request->shift_type,
            'status'     => $request->status ?? 'approved',
            'notes'      => $request->notes,
            'logged_by'  => $request->user()->id,
            'approved_by'=> $request->user()->id,
            'approved_at'=> now(),
        ]);

        $log->load(['user', 'logger', 'approver']);

        $this->audit->logFromRequest(
            $request, 'time_log_created',
            "Manual time log created for {$log->user->name} on {$log->date->format('Y-m-d')}",
            $log
        );

        return response()->json(['data' => new TimeLogResource($log)], 201);
    }

    public function update(StoreTimeLogRequest $request, TimeLog $timeLog): JsonResponse
    {
        $timeLog->update([
            'user_id'    => $request->user_id,
            'date'       => $request->date,
            'clock_in'   => $request->clock_in,
            'clock_out'  => $request->clock_out,
            'shift_type' => $request->shift_type,
            'status'     => $request->status ?? $timeLog->status,
            'notes'      => $request->notes,
        ]);

        $timeLog->load(['user', 'logger', 'approver']);
        $this->audit->logFromRequest($request, 'time_log_updated', "Time log #{$timeLog->id} updated", $timeLog);

        return response()->json(['data' => new TimeLogResource($timeLog)]);
    }

    public function approve(Request $request, TimeLog $timeLog): JsonResponse
    {
        $timeLog->update([
            'status'      => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $timeLog->load(['user', 'logger', 'approver']);
        $this->audit->logFromRequest($request, 'time_log_approved', "Approved time log #{$timeLog->id} for {$timeLog->user->name}", $timeLog);

        return response()->json(['data' => new TimeLogResource($timeLog)]);
    }

    public function destroy(Request $request, TimeLog $timeLog): JsonResponse
    {
        $this->audit->logFromRequest($request, 'time_log_deleted', "Time log #{$timeLog->id} deleted");
        $timeLog->delete();

        return response()->json(['message' => 'Time log deleted.']);
    }

    /** Manager: attendance & hours summary by user for a date range. */
    public function summary(Request $request): JsonResponse
    {
        $from = $request->date_from ?? Carbon::now()->subDays(6)->toDateString();
        $to   = $request->date_to   ?? Carbon::now()->toDateString();

        $logs = TimeLog::with('user:id,name,role,department')
            ->whereBetween('date', [$from, $to])
            ->get();

        $byUser = $logs->groupBy('user_id')->map(function ($items) {
            $totalHours = $items->sum(fn ($l) => $l->hours_worked ?? 0);
            $first = $items->first();
            return [
                'user'        => $first->user ? [
                    'id'         => $first->user->id,
                    'name'       => $first->user->name,
                    'role'       => $first->user->role,
                    'department' => $first->user->department,
                ] : null,
                'sessions'    => $items->count(),
                'total_hours' => round($totalHours, 2),
                'open'        => $items->whereNull('clock_out')->count(),
                'pending'     => $items->where('status', 'pending')->count(),
            ];
        })->sortByDesc('total_hours')->values();

        return response()->json([
            'data' => $byUser,
            'meta' => [
                'date_from'            => $from,
                'date_to'              => $to,
                'currently_clocked_in' => TimeLog::whereNull('clock_out')->count(),
                'pending_approval'     => TimeLog::where('status', 'pending')->count(),
            ],
        ]);
    }
}
