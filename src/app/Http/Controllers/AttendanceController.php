<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', auth()->id())
            ->where('date', $today)
            ->first();
        return view('generals.index', compact('attendance'));
    }

    // 出勤
    public function clockIn()
    {
        
        $today = now()->toDateString();

        Attendance::create([
            'user_id' => Auth::id(),
            'date' => $today,
            'clock_in' => now(),
            'work_status' => 'working',
        ]);

        return redirect()->back();
    }

    // 休憩入
    public function breakIn()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->where('date', now()->toDateString())
            ->firstOrFail();

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);

        $attendance->update([
            'work_status' => 'on_break',
        ]);

        return redirect()->back();
    }

    // 休憩戻
    public function breakOut()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->where('date', now()->toDateString())
            ->firstOrFail();

        $break = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_end')
            ->latest()
            ->firstOrFail();

        $break->update([
            'break_end' => now(),
        ]);

        $attendance->update([
            'work_status' => 'working',
        ]);

        return redirect()->back();
    }

    // 退勤
    public function clockOut()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->where('date', now()->toDateString())
            ->firstOrFail();

        $attendance->update([
            'clock_out' => now(),
            'work_status' => 'finished',
        ]);

        return redirect()->back();
    }

    // 勤怠一覧
    public function list(Request $request)
    {
        $user = auth()->user();

        // 月を表示
        $month = $request->input('month')
        ? Carbon::createFromFormat('Y-m', $request->input('month'))
        : now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
         
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date')
            ->get();

        return view('generals.list', compact('attendances', 'month'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
