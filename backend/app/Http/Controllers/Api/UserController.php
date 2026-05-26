<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->orderBy('name');

        if ($request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('username', 'like', "%{$request->search}%");
            });
        }

        $users = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'total'        => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name'       => $request->name,
            'username'   => $request->username,
            'email'      => $request->email,
            'password'   => $request->password,
            'role'       => $request->role,
            'department' => $request->department ?? 'other',
            'position'   => $request->position,
            'is_active'  => $request->is_active ?? true,
        ]);

        $this->audit->logFromRequest(
            $request,
            'user_created',
            "Created user {$user->name} ({$user->role_label})",
            $user
        );

        return response()->json(['data' => new UserResource($user)], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(StoreUserRequest $request, User $user): JsonResponse
    {
        $data = $request->only(['name', 'username', 'email', 'role', 'department', 'position', 'is_active']);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        $this->audit->logFromRequest($request, 'user_updated', "Updated user {$user->name}", $user);

        return response()->json(['data' => new UserResource($user)]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $adminCount = User::where('role', 'admin')->where('is_active', true)->count();
        if ($user->role === 'admin' && $user->is_active && $adminCount <= 1) {
            return response()->json(['message' => 'Cannot disable the last admin account.'], 422);
        }

        $user->update(['is_active' => false]);
        $user->tokens()->delete();

        $this->audit->logFromRequest($request, 'user_disabled', "Disabled user {$user->name}");

        return response()->json(['message' => 'User disabled.']);
    }

    public function reactivate(Request $request, User $user): JsonResponse
    {
        $user->update(['is_active' => true]);
        $this->audit->logFromRequest($request, 'user_reactivated', "Reactivated user {$user->name}");

        return response()->json(['data' => new UserResource($user)]);
    }

    public function employees(): JsonResponse
    {
        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'department', 'position']);

        return response()->json(['data' => $users]);
    }
}
