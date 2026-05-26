<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTipRequest;
use App\Http\Resources\TipResource;
use App\Models\Tip;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Tip::with(['employee', 'recorder'])->orderByDesc('date')->orderByDesc('created_at');

        // Employees can only see their own tips
        if ($user->role === 'employee') {
            $query->where('employee_id', $user->id);
        }

        if ($request->employee_id && $user->role !== 'employee') {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->date_from && $request->date_to) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        if ($request->date) {
            $query->whereDate('date', $request->date);
        }

        $tips = $query->paginate($request->per_page ?? 50);

        $totalAmount = (clone $query)->sum('amount');

        return response()->json([
            'data'  => TipResource::collection($tips->items()),
            'meta'  => [
                'total'        => $tips->total(),
                'current_page' => $tips->currentPage(),
                'last_page'    => $tips->lastPage(),
                'per_page'     => $tips->perPage(),
                'total_amount' => round($totalAmount, 2),
            ],
        ]);
    }

    public function store(StoreTipRequest $request): JsonResponse
    {
        $tip = Tip::create([
            'employee_id' => $request->employee_id,
            'amount'      => $request->amount,
            'date'        => $request->date,
            'note'        => $request->note,
            'recorded_by' => $request->user()->id,
        ]);

        $tip->load(['employee', 'recorder']);

        $this->audit->logFromRequest(
            $request,
            'tip_created',
            "Tip \${$tip->amount} recorded for {$tip->employee->name} on {$tip->date->format('Y-m-d')}",
            $tip
        );

        return response()->json(['data' => new TipResource($tip)], 201);
    }

    public function update(StoreTipRequest $request, Tip $tip): JsonResponse
    {
        $tip->update([
            'employee_id' => $request->employee_id,
            'amount'      => $request->amount,
            'date'        => $request->date,
            'note'        => $request->note,
        ]);

        $tip->load(['employee', 'recorder']);
        $this->audit->logFromRequest($request, 'tip_updated', "Tip #{$tip->id} updated to \${$tip->amount}", $tip);

        return response()->json(['data' => new TipResource($tip)]);
    }

    public function destroy(Request $request, Tip $tip): JsonResponse
    {
        $this->audit->logFromRequest($request, 'tip_deleted', "Tip #{$tip->id} deleted");
        $tip->delete();

        return response()->json(['message' => 'Tip deleted.']);
    }

    public function analytics(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to'   => ['required', 'date_format:Y-m-d'],
        ]);

        $byEmployee = Tip::selectRaw('employee_id, SUM(amount) as total, COUNT(*) as count')
            ->with('employee:id,name')
            ->whereBetween('date', [$request->from, $request->to])
            ->groupBy('employee_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'employee' => $r->employee ? ['id' => $r->employee->id, 'name' => $r->employee->name] : null,
                'total'    => round($r->total, 2),
                'count'    => $r->count,
            ]);

        $byDate = Tip::selectRaw('date, SUM(amount) as total, COUNT(*) as count')
            ->whereBetween('date', [$request->from, $request->to])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date'  => $r->date,
                'total' => round($r->total, 2),
                'count' => $r->count,
            ]);

        return response()->json([
            'by_employee' => $byEmployee,
            'by_date'     => $byDate,
            'grand_total' => round(Tip::whereBetween('date', [$request->from, $request->to])->sum('amount'), 2),
        ]);
    }
}
