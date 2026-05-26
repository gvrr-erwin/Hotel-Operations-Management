<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOperationsTaskRequest;
use App\Http\Resources\OperationsTaskResource;
use App\Models\OperationsTask;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperationsTaskController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = OperationsTask::with(['assignee', 'creator'])->orderByDesc('created_at');

        // Staff see tasks assigned to them or created by them
        if (! $user->isManagement()) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }
        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->mine) {
            $query->where('assigned_to', $user->id);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('room_number', 'like', "%{$request->search}%");
            });
        }

        return response()->json([
            'data' => OperationsTaskResource::collection($query->get()),
            'meta' => [
                'counts' => [
                    'open'        => OperationsTask::where('status', 'open')->count(),
                    'in_progress' => OperationsTask::where('status', 'in_progress')->count(),
                    'completed'   => OperationsTask::where('status', 'completed')->count(),
                    'overdue'     => OperationsTask::where('due_at', '<', now())
                        ->whereNotIn('status', ['completed', 'cancelled'])->count(),
                ],
            ],
        ]);
    }

    public function store(StoreOperationsTaskRequest $request): JsonResponse
    {
        $task = OperationsTask::create([
            'title'       => $request->title,
            'description' => $request->description,
            'category'    => $request->category,
            'priority'    => $request->priority,
            'status'      => $request->status ?? 'open',
            'room_number' => $request->room_number,
            'assigned_to' => $request->assigned_to,
            'due_at'      => $request->due_at,
            'created_by'  => $request->user()->id,
        ]);

        $task->load(['assignee', 'creator']);
        $this->audit->logFromRequest(
            $request, 'task_created',
            "Created task '{$task->title}' [{$task->category}/{$task->priority}]",
            $task
        );

        return response()->json(['data' => new OperationsTaskResource($task)], 201);
    }

    public function update(StoreOperationsTaskRequest $request, OperationsTask $task): JsonResponse
    {
        $this->authorizeEdit($request, $task);

        $data = $request->only([
            'title', 'description', 'category', 'priority', 'status',
            'room_number', 'assigned_to', 'due_at',
        ]);

        if (($data['status'] ?? null) === 'completed' && ! $task->completed_at) {
            $data['completed_at'] = now();
        }
        if (($data['status'] ?? null) !== 'completed') {
            $data['completed_at'] = null;
        }

        $task->update($data);
        $task->load(['assignee', 'creator']);

        $this->audit->logFromRequest($request, 'task_updated', "Task #{$task->id} updated", $task);

        return response()->json(['data' => new OperationsTaskResource($task)]);
    }

    /** Lightweight status update — assignee can advance their own task. */
    public function updateStatus(Request $request, OperationsTask $task): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'completed', 'cancelled'])],
        ]);

        $user = $request->user();
        if (! $user->isManagement() && $task->assigned_to !== $user->id) {
            return response()->json(['message' => 'You can only update tasks assigned to you.'], 403);
        }

        $task->update([
            'status'       => $request->status,
            'completed_at' => $request->status === 'completed' ? now() : null,
        ]);

        $task->load(['assignee', 'creator']);
        $this->audit->logFromRequest($request, 'task_status_changed', "Task #{$task->id} -> {$task->status}", $task);

        return response()->json(['data' => new OperationsTaskResource($task)]);
    }

    public function destroy(Request $request, OperationsTask $task): JsonResponse
    {
        $this->authorizeEdit($request, $task);
        $this->audit->logFromRequest($request, 'task_deleted', "Task #{$task->id} deleted");
        $task->delete();

        return response()->json(['message' => 'Task removed.']);
    }

    private function authorizeEdit(Request $request, OperationsTask $task): void
    {
        $user = $request->user();
        if ($user->isManagement()) {
            return;
        }
        if ($task->created_by === $user->id) {
            return;
        }
        abort(403, 'You cannot modify this task.');
    }
}
