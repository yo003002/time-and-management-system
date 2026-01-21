<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrection;
use App\Models\User;

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

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        
        // 勤怠データ
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($a) => $a->date->format('Y-m-d'));

        $dates = CarbonPeriod::create($start, $end);

        $rows = collect($dates)->map(function ($date) use ($attendances) {
            $attendance = $attendances->get($date->format('Y-m-d'));

            return [
                'date' => $date,
                'attendance' => $attendance,
                'weekday' => ['日','月','火','水','木','金','土'][$date->dayOfWeek],
            ];
        });

        return view('generals.list', compact('rows', 'month'));
    }

    // 勤怠詳細
    public function detail($id)
    {
        $attendance = Attendance::with([
            'breaks',
            'approvedCorrection',
            'pendingCorrection',
            'user',
        ])->findOrFail($id);

        // 自分の勤怠のみ確認できる
        abort_if($attendance->user_id !== auth()->id(), 403);

        $correction = 
            $attendance->approvedCorrection
            ?? $attendance->pendingCorrection;

        // 承認待ちの判定
        $isPending = $correction && $correction->status === 'pending';

        $remark = $correction?->remark ?? '';

        // 日付表示
        $date = Carbon::parse($attendance->date);

        // 出勤・退勤（修正があれば優先）
        $clockIn = $correction?->clock_in ?? $attendance->clock_in;
        $clockOut = $correction?->clock_out ?? $attendance->clock_out;


        // 休憩
        $hasCorrection = !is_null($correction);
        
        $displayBreaks = collect($correction?->breaks ?? [])
            ->map(fn ($b) => (object) [
                'start' => $b['start'] ?? '',
                'end' => $b['end'] ?? '',
            ]);

        if ($displayBreaks->isEmpty()) {
            $displayBreaks = $attendance->breaks->map(fn ($b) => (object) [
                'start' => optional($b->break_start)->format('H:i'),
                'end'  => optional($b->break_end)->format('H:i'),
            ]);
        }
        
        if (!$hasCorrection) {
            $displayBreaks->push((object) [
                'start' => '',
                'end' => '',
            ]);
        }

        return view('generals.detail', compact(
            'attendance',
            'correction',
            'isPending',
            'date',
            'clockIn',
            'clockOut',
            'displayBreaks',
            'remark',
        ));
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
    public function store(Request $request, $attendanceId)
    {
        $attendance = Attendance::findOrFail($attendanceId);

        abort_if($attendance->user_id !== auth()->id(), 403);

        $breaks = collect($request->breaks ?? [])
            ->filter(function ($break) {
                return !empty($break['start']) && !empty($break['end']);
            })
            ->values()
            ->toArray();

        $clockIn = $request->clock_in
        ? Carbon::parse($attendance->date)->setTimeFromTimeString($request->clock_in)
        : null;

        $clockOut = $request->clock_out
        ? Carbon::parse($attendance->date)->setTimeFromTimeString($request->clock_out)
        : null;

        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'breaks' => $breaks,
            'remark' => $request->remark,
            'status' => 'pending',
        ]);

        return redirect()->route('generals.detail', $attendance->id);
    }

    /**
     * Display the specified resource.
     */
    public function adminList(Request $request)
    {
        $date = $request->date
            ? Carbon::createFromFormat('Y-m-d', $request->date)
            : Carbon::today();

        $users = User::where('role', 'user')
            ->whereHas('attendances', function ($query) use ($date) {
                $query->whereDate('date', $date);
            })
            ->with(['attendances' => function ($query) use ($date) {
                $query->whereDate('date', $date)
                    ->with('breaks');
            }])
            ->get();

        return view('admin.list', compact('users', 'date'));
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
