<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'kpis'           => $this->dashboard->getKpis(),
            'rate_trend'     => $this->dashboard->getRateTrend(30),
            'tip_trend'      => $this->dashboard->getTipTrend(30),
            'top_earners'    => $this->dashboard->getTopEarners(5),
            'recent_activity'=> $this->dashboard->getRecentActivity(10),
            'shift_summary'  => $this->dashboard->getShiftSummaryToday(),
            'today_shifts'   => $this->dashboard->getTodayShifts(),
            'open_tasks'     => $this->dashboard->getOpenTasks(8),
        ]);
    }
}
