<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AttendanceController;



Route::get('/admin/login', function () {
    return view('admin.login');
})->middleware('guest')->name('admin.login');

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return '管理者ダッシュボード';
    })->middleware('can:admin');
});

Route::middleware('auth')->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('generals.index');

    // 出勤
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('generals.clockIn');

    // 休憩入
    Route::post('/attendance/break-in', [AttendanceController::class, 'breakIn'])->name('generals.breakIn');

    // 休憩戻
    Route::post('/attendance/break-out', [AttendanceController::class, 'breakOut'])->name('generals.breakOut');

    // 退勤
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('generals.clockOut');

    // 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('generals.list');

    // 勤怠詳細画面
    Route::get('/attendance/detail/{id}', [Attendancecontroller::class, 'detail'])->name('generals.detail');
});


