<?php

namespace App\Services;

use App\Models\HotelRate;
use App\Models\OperationsTask;
use App\Models\Shift;
use App\Models\Tip;
use App\Models\TimeLog;
use App\Models\User;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getKpis(): array
    {
        $today     = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();

        $todayRatesCount     = HotelRate::whereDate('date', $today)->count();
        $yesterdayRatesCount = HotelRate::whereDate('date', $yesterday)->count();

        $avgRateToday     = HotelRate::whereDate('date', $today)->avg('rate') ?? 0;
        $avgRateYesterday = HotelRate::whereDate('date', $yesterday)->avg('rate') ?? 0;

        $tipsToday     = Tip::whereDate('date', $today)->sum('amount');
        $tipsYesterday = Tip::whereDate('date', $yesterday)->sum('amount');
        $tipsMonth     = Tip::whereDate('date', '>=', $monthStart)->sum('amount');

        $activeStaff    = User::where('is_active', true)->count();
        $clockedInToday = TimeLog::whereDate('date', $today)->whereNull('clock_out')->count();

        return [
            'rates' => [
                'today_count'         => $todayRatesCount,
                'yesterday_count'     => $yesterdayRatesCount,
                'avg_rate_today'      => round($avgRateToday, 2),
                'avg_rate_yesterday'  => round($avgRateYesterday, 2),
                'avg_rate_change_pct' => $avgRateYesterday > 0
                    ? round((($avgRateToday - $avgRateYesterday) / $avgRateYesterday) * 100, 1)
                    : null,
            ],
            'tips' => [
                'today'    => round($tipsToday, 2),
                'yesterday'=> round($tipsYesterday, 2),
                'month'    => round($tipsMonth, 2),
                'change_pct' => $tipsYesterday > 0
                    ? round((($tipsToday - $tipsYesterday) / $tipsYesterday) * 100, 1)
                    : null,
            ],
            'staff' => [
                'active'       => $activeStaff,
                'clocked_in'   => $clockedInToday,
            ],
            'tasks' => [
                'open'        => OperationsTask::where('status', 'open')->count(),
                'in_progress' => OperationsTask::where('status', 'in_progress')->count(),
                'overdue'     => OperationsTask::where('due_at', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'urgent_open' => OperationsTask::where('priority', 'urgent')
                    ->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ],
            'shifts' => [
                'scheduled_today' => Shift::whereDate('date', $today)
                    ->where('status', '!=', 'cancelled')->count(),
                'pending_logs'    => TimeLog::where('status', 'pending')->count(),
            ],
        ];
    }

    public function getTodayShifts(): array
    {
        return Shift::with('user:id,name,role,department')
            ->whereDate('date', Carbon::today())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($s) => [
                'id'         => $s->id,
                'user'       => $s->user ? ['id' => $s->user->id, 'name' => $s->user->name, 'role' => $s->user->role] : null,
                'shift_type' => $s->shift_type,
                'start_time' => $s->start_time,
                'end_time'   => $s->end_time,
            ])->toArray();
    }

    public function getOpenTasks(int $limit = 8): array
    {
        return OperationsTask::with('assignee:id,name')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderBy('due_at')
            ->limit($limit)
            ->get()
            ->map(fn ($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'category'    => $t->category,
                'priority'    => $t->priority,
                'status'      => $t->status,
                'room_number' => $t->room_number,
                'assignee'    => $t->assignee ? ['id' => $t->assignee->id, 'name' => $t->assignee->name] : null,
                'due_at'      => $t->due_at?->toIso8601String(),
                'is_overdue'  => $t->is_overdue,
            ])->toArray();
    }

    public function getRateTrend(int $days = 30): array
    {
        $from = Carbon::now()->subDays($days - 1)->toDateString();

        return HotelRate::selectRaw('date, AVG(rate) as avg_rate, COUNT(*) as count')
            ->where('date', '>=', $from)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date'     => $r->date,
                'avg_rate' => round($r->avg_rate, 2),
                'count'    => $r->count,
            ])
            ->values()
            ->toArray();
    }

    public function getTipTrend(int $days = 30): array
    {
        $from = Carbon::now()->subDays($days - 1)->toDateString();

        return Tip::selectRaw('date, SUM(amount) as total, COUNT(*) as count')
            ->where('date', '>=', $from)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date'  => $r->date,
                'total' => round($r->total, 2),
                'count' => $r->count,
            ])
            ->values()
            ->toArray();
    }

    public function getTopEarners(int $limit = 5): array
    {
        return Tip::selectRaw('employee_id, SUM(amount) as total, COUNT(*) as tip_count')
            ->with('employee:id,name,role')
            ->groupBy('employee_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'employee' => $r->employee ? ['id' => $r->employee->id, 'name' => $r->employee->name] : null,
                'total'    => round($r->total, 2),
                'count'    => $r->tip_count,
            ])
            ->toArray();
    }

    public function getRecentActivity(int $limit = 10): array
    {
        return AuditLog::with('user:id,name,role')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($a) => [
                'id'          => $a->id,
                'action'      => $a->action,
                'description' => $a->description,
                'user'        => $a->user ? ['id' => $a->user->id, 'name' => $a->user->name] : null,
                'created_at'  => $a->created_at->toIso8601String(),
            ])
            ->toArray();
    }

    public function getShiftSummaryToday(): array
    {
        $today = Carbon::today()->toDateString();

        return TimeLog::selectRaw('shift_type, COUNT(*) as count')
            ->whereDate('date', $today)
            ->groupBy('shift_type')
            ->pluck('count', 'shift_type')
            ->toArray();
    }
}
