<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\PrizeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EventDashboardController;
use App\Http\Controllers\WinnerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $event = \App\Models\Event::whereIn('status', ['aktif', 'spin'])->latest('id')->first();

    return view('welcome', [
        'event' => $event,
        'prizes' => $event?->prizes()->withCount(['winners as taken' => fn ($q) => $q->whereIn('status', ['menang', 'diserahkan'])])->get() ?? collect(),
    ]);
});

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::prefix('absensi')->middleware('throttle:absensi')->group(function () {
    Route::get('/', [ParticipantController::class, 'index']);
    Route::post('/check-nik', [ParticipantController::class, 'checkNik']);
    Route::post('/', [ParticipantController::class, 'store']);
});

Route::prefix('peserta')->group(function () {
    Route::get('/', [ParticipantController::class, 'dashboard']);
    Route::get('/pemenang', [ParticipantController::class, 'winners']);
    Route::post('/logout', [ParticipantController::class, 'logout']);
});

Route::get('/display/{token}', fn (string $token) => view('display.index', ['token' => $token]));
Route::get('/display/{token}/state', [OperatorController::class, 'displayState']);
Route::post('/display/{token}/trigger/{spinId}', [OperatorController::class, 'trigger']);
Route::post('/display/{token}/finish/{spinId}', [OperatorController::class, 'finish']);
Route::post('/display/{token}/complete/{spinId}', [OperatorController::class, 'complete']);

Route::middleware(['auth', 'role:admin,operator'])->group(function () {
    Route::get('/event', EventDashboardController::class)->name('event.dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/operator', [OperatorController::class, 'index']);
    Route::post('/operator/spin', [OperatorController::class, 'arm']);
    Route::post('/operator/spin/{spinId}/cancel', [OperatorController::class, 'disarm']);
    Route::get('/operator/kupon', [OperatorController::class, 'couponPage']);
    Route::post('/operator/kupon/handover', [OperatorController::class, 'handover']);
    Route::post('/operator/kupon/{winnerId}/cancel', [OperatorController::class, 'cancel']);
});

Route::middleware(['auth', 'role:admin,operator'])->prefix('admin')->group(function () {
    Route::resource('employees', EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('employees-import', [EmployeeController::class, 'importForm']);
    Route::post('employees-import', [EmployeeController::class, 'import']);
    Route::get('employees-template', [EmployeeController::class, 'template']);

    Route::get('events/{event}/edit', [EventController::class, 'edit']);
    Route::put('events/{event}', [EventController::class, 'update']);
    Route::post('events/{event}/rotate', [EventController::class, 'rotate']);
    Route::get('events/{event}/prizes', [PrizeController::class, 'index']);
    Route::post('events/{event}/prizes', [PrizeController::class, 'store']);
    Route::put('prizes/{prize}', [PrizeController::class, 'update']);
    Route::delete('prizes/{prize}', [PrizeController::class, 'destroy']);
    Route::get('events/{event}/attendance', [AttendanceController::class, 'index']);
    Route::get('events/{event}/attendance/export', [AttendanceController::class, 'export']);
    Route::get('events/{event}/winners', [WinnerController::class, 'index']);
    Route::get('events/{event}/winners/export', [WinnerController::class, 'export']);
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('events', [EventController::class, 'index']);
    Route::get('events/create', [EventController::class, 'create']);
    Route::post('events', [EventController::class, 'store']);
    Route::delete('events/{event}', [EventController::class, 'destroy']);
});
