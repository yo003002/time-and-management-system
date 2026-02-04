<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AttendanceController;

// 管理者用ログイン画面
Route::get('/admin/login', function () {
    return view('admin.login');
})->middleware('guest')->name('admin.login');

Route::get('/', function() {
    return redirect()->route('generals.index');
});

// 一般
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('generals.index');

    // 出勤
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('generals.clockIn');

    // 休憩入
    Route::post('/attendance/break-in', [AttendanceController::class, 'breakIn'])->name('generals.breakIn');

    // 休憩戻
    Route::post('/attendance/break-out', [AttendanceController::class, 'breakOut'])->name('generals.breakOut');

    // 退勤
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('generals.clockOut');

    // 月次勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('generals.list');

    // 勤怠詳細画面
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('generals.detail');

    // 修正
    Route::post('/attendance/detail/{id}/correction', [AttendanceController::class, 'store'])->name('generals.correction.store');

    
});
    

// 管理者のみメール認証除外
Route::middleware(['auth', 'verified.except.admin'])->group(function () {
    // 申請一覧画面（一般・管理者共通）
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'correctionList'])->name('corrections.list');
});
    
// 管理者
Route::middleware(['auth', 'can:admin'])->group(function () {
    
    // 管理者ログイン後勤怠一覧
    Route::get('/admin/attendance/list', [AttendanceController::class, 'adminList'])->name('admin.list');

    // 詳細
    Route::get('/admin/attendance/{id}', [AttendanceController::class, 'adminDetail'])->name('admin.detail');

    // 修正
    Route::post('/admin/attendance/{id}/correction', [AttendanceController::class, 'adminStore'])->name('admin.correction.store');

    // スタッフ一覧
    Route::get('/admin/staff/list', [AttendanceController::class, 'staffList'])->name('admin.staffList');

    // スタッフの月次勤怠一覧
    Route::get('/admin/attendance/staff/{id}', [AttendanceController::class, 'staffAttendanceList'])->name('admin.staffAttendanceList');

    // スタッフの月次勤怠一覧のCSV出力
    Route::get('/admin/attendance/staff/{id}/csv', [AttendanceController::class, 'exportStaffMonthly'])->name('admin.attendance.staff.csv');

    // 修正申請承認画面
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceController::class, 'approveForm'])->name('correction.approve.form');

    Route::patch('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceController::class, 'approve'])->name('correction.approve');
});
