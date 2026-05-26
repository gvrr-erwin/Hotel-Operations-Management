<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HotelRateController;
use App\Http\Controllers\Api\OperationsTaskController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\TimeLogController;
use App\Http\Controllers\Api\TipController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);

// ── Authenticated ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Dashboard — admin + GM + Assistant GM
    Route::middleware('role:admin,general_manager,assistant_manager')
        ->get('/dashboard', [DashboardController::class, 'index']);

    // Hotel Rates
    Route::prefix('rates')->group(function () {
        Route::get('/', [HotelRateController::class, 'index']);
        Route::get('/hotels', [HotelRateController::class, 'hotels']);
        Route::get('/room-types', [HotelRateController::class, 'roomTypes']);
        Route::get('/compare', [HotelRateController::class, 'compare']);
        Route::get('/historical', [HotelRateController::class, 'historical']);

        Route::middleware('role:admin,general_manager')->group(function () {
            Route::post('/', [HotelRateController::class, 'store']);
            Route::put('/{rate}', [HotelRateController::class, 'update']);
            Route::delete('/{rate}', [HotelRateController::class, 'destroy']);
        });
    });

    // Tips
    Route::prefix('tips')->group(function () {
        Route::get('/', [TipController::class, 'index']);
        Route::get('/analytics', [TipController::class, 'analytics']);

        Route::middleware('role:admin,general_manager,assistant_manager')->group(function () {
            Route::post('/', [TipController::class, 'store']);
            Route::put('/{tip}', [TipController::class, 'update']);
            Route::delete('/{tip}', [TipController::class, 'destroy']);
        });
    });

    // Time Logs — clock-in/out available to ALL authenticated users
    Route::prefix('time-logs')->group(function () {
        Route::get('/',              [TimeLogController::class, 'index']);
        Route::get('/active',        [TimeLogController::class, 'active']);
        Route::post('/clock-in',     [TimeLogController::class, 'clockIn']);
        Route::post('/clock-out',    [TimeLogController::class, 'clockOut']);

        // Management-only: corrections, approvals, summary
        Route::middleware('role:admin,general_manager,assistant_manager')->group(function () {
            Route::get('/summary',         [TimeLogController::class, 'summary']);
            Route::post('/',               [TimeLogController::class, 'store']);
            Route::put('/{timeLog}',       [TimeLogController::class, 'update']);
            Route::post('/{timeLog}/approve', [TimeLogController::class, 'approve']);
            Route::delete('/{timeLog}',    [TimeLogController::class, 'destroy']);
        });
    });

    // Shift Scheduling
    Route::prefix('shifts')->group(function () {
        Route::get('/', [ShiftController::class, 'index']);

        Route::middleware('role:admin,general_manager,assistant_manager')->group(function () {
            Route::post('/',          [ShiftController::class, 'store']);
            Route::put('/{shift}',    [ShiftController::class, 'update']);
            Route::delete('/{shift}', [ShiftController::class, 'destroy']);
        });
    });

    // Operations Task Board — open to all; status updates restricted internally
    Route::prefix('tasks')->group(function () {
        Route::get('/',                  [OperationsTaskController::class, 'index']);
        Route::post('/',                 [OperationsTaskController::class, 'store']);
        Route::patch('/{task}/status',   [OperationsTaskController::class, 'updateStatus']);
        Route::put('/{task}',            [OperationsTaskController::class, 'update']);
        Route::delete('/{task}',         [OperationsTaskController::class, 'destroy']);
    });

    // Users — admin only
    Route::middleware('role:admin')->prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        Route::patch('/{user}/reactivate', [UserController::class, 'reactivate']);
    });

    // Shared employee list (for forms)
    Route::get('/employees', [UserController::class, 'employees']);

    // Audit Logs — admin only
    Route::middleware('role:admin')
        ->get('/audit-logs', [AuditLogController::class, 'index']);
});
