<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes here are automatically prefixed with /api and have the
| api middleware group applied (stateless, Sanctum token auth).
|
*/

// ── AUTH ──────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {

    // Public: login does not require an existing token
    Route::post('login', [LoginController::class, 'login']);

    // Protected: require a valid Sanctum token
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [LoginController::class, 'logout']);
        Route::get('me',     [LoginController::class, 'me']);
    });

});

// ── ENTITIES ──────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'entity.scope'])
    ->prefix('entities')
    ->group(function () {
        Route::get('/', [EntityController::class, 'index'])
            ->middleware('permission:entities.view');

        Route::post('/', [EntityController::class, 'store'])
            ->middleware('role:super_admin');

        Route::get('/{entity}', [EntityController::class, 'show'])
            ->middleware('permission:entities.view');

        Route::put('/{entity}', [EntityController::class, 'update'])
            ->middleware('role:super_admin');

        Route::delete('/{entity}', [EntityController::class, 'destroy'])
            ->middleware('role:super_admin');
    });

// ── EMPLOYEES ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'entity.scope'])
    ->prefix('employees')
    ->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])
            ->middleware('permission:employees.view');

        Route::post('/', [EmployeeController::class, 'store'])
            ->middleware('permission:employees.create');

        // Import must be declared before /{user} to avoid route collision
        Route::post('/import', [EmployeeController::class, 'import'])
            ->middleware('permission:employees.import');

        Route::get('/{user}', [EmployeeController::class, 'show'])
            ->middleware('permission:employees.view');

        Route::put('/{user}', [EmployeeController::class, 'update'])
            ->middleware('permission:employees.update');

        Route::delete('/{user}', [EmployeeController::class, 'destroy'])
            ->middleware('permission:employees.delete');

        // Dual employment sub-resources
        Route::post('/{user}/employments', [EmployeeController::class, 'addEmployment'])
            ->middleware('permission:employees.create');

        Route::put('/{user}/employments/{employment}', [EmployeeController::class, 'updateEmployment'])
            ->middleware('permission:employees.update');
    });

// ── ATTENDANCE ────────────────────────────────────────────────────────────
// No entity.scope here — employees can clock in/out from any location;
// entity isolation is enforced inside the controller via active employment.
Route::middleware(['auth:sanctum'])
    ->prefix('attendance')
    ->group(function () {
        Route::post('/clock-in', [AttendanceController::class, 'clockIn'])
            ->middleware('permission:attendance.clock_in');

        // clock-out intentionally uses the same permission as clock-in (one permission covers both directions)
        Route::post('/clock-out', [AttendanceController::class, 'clockOut'])
            ->middleware('permission:attendance.clock_in');

        Route::get('/today', [AttendanceController::class, 'today'])
            ->middleware('permission:attendance.view_own');

        // Returns own records for employees; all records for managers (controller-filtered)
        Route::get('/', [AttendanceController::class, 'index'])
            ->middleware('permission:attendance.view_own');

        Route::put('/{attendance}/correct', [AttendanceController::class, 'correct'])
            ->middleware(['entity.scope', 'permission:attendance.correct']);
    });

// ── LEAVE ─────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'entity.scope'])
    ->prefix('leave')
    ->group(function () {
        Route::get('/types', [LeaveController::class, 'types'])
            ->middleware('permission:leave.view_own');

        Route::get('/balances', [LeaveController::class, 'balances'])
            ->middleware('permission:leave.view_own');

        Route::get('/requests', [LeaveController::class, 'index'])
            ->middleware('permission:leave.view_own');

        Route::post('/requests', [LeaveController::class, 'store'])
            ->middleware('permission:leave.request');

        Route::put('/requests/{leaveRequest}/approve', [LeaveController::class, 'approve'])
            ->middleware('permission:leave.approve');

        Route::put('/requests/{leaveRequest}/reject', [LeaveController::class, 'reject'])
            ->middleware('permission:leave.approve');

        Route::delete('/requests/{leaveRequest}', [LeaveController::class, 'cancel'])
            ->middleware('permission:leave.request');
    });

// ── PAYROLL ───────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'entity.scope'])
    ->prefix('payroll')
    ->group(function () {
        Route::get('/runs', [PayrollController::class, 'index'])
            ->middleware('permission:payroll.view_all');

        Route::post('/runs', [PayrollController::class, 'store'])
            ->middleware('permission:payroll.process');

        Route::get('/runs/{run}', [PayrollController::class, 'show'])
            ->middleware('permission:payroll.view_all');

        Route::post('/runs/{run}/process', [PayrollController::class, 'process'])
            ->middleware('permission:payroll.process');

        Route::post('/runs/{run}/lock', [PayrollController::class, 'lock'])
            ->middleware('permission:payroll.lock');

        Route::get('/runs/{run}/export', [PayrollController::class, 'export'])
            ->middleware('permission:payroll.view_all');

        Route::get('/runs/{run}/items', [PayrollController::class, 'items'])
            ->middleware('permission:payroll.view_all');

        // Controller validates slip ownership — any employee with this permission
        // can only retrieve their own slip; admins get any slip
        Route::get('/items/{item}/slip', [PayrollController::class, 'slip'])
            ->middleware('permission:payroll.view_own_slip');

        // Streams PDF from local (private) storage — same ownership rules as above
        Route::get('/items/{item}/slip-download', [PayrollController::class, 'downloadSlip'])
            ->middleware('permission:payroll.view_own_slip');
    });

// ── LOCATIONS (QR & Geofence config) ─────────────────────────────────────
Route::middleware(['auth:sanctum', 'entity.scope'])
    ->prefix('locations')
    ->group(function () {
        Route::get('/', [LocationController::class, 'index'])
            ->middleware('permission:attendance.view_all');

        Route::post('/', [LocationController::class, 'store'])
            ->middleware('role:entity_admin,super_admin');

        Route::get('/{location}/qr', [LocationController::class, 'qrCode'])
            ->middleware('permission:attendance.clock_in');

        Route::post('/{location}/qr/rotate', [LocationController::class, 'rotateQr'])
            ->middleware('role:entity_admin,super_admin');
    });
