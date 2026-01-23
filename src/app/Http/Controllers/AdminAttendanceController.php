<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrection;
use App\Models\User;

class AdminAttendanceController extends Controller
{
    // その日の勤怠一覧（管理者）
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
            ->get()
            ->map(function ($user) {
                $user->attendance = $user->attendances->first();
                return $user;
            });

        return view('admin.list', compact('users', 'date'));
    }

    // 勤怠詳細（管理者）
    public function adminDetail($id)
    {
        $attendance = Attendance::with([
            'breaks',
            'approvedCorrection',
            'pendingCorrection',
            'user',
        ])->findOrFail($id);

        abort_if(auth()->user()->role !== 'admin', 403);

        $correction = 
            $attendance->pendingCorrection
            ?? $attendance->approvedCorrection;

        $isPending = $correction && $correction->status === 'pending';

        $remark = $correction?->remark ?? '';

        $date = Carbon::parse($attendance->date);

        $clockIn = $correction?->clock_in ?? $attendance->clock_in;
        $clockOut = $correction?->clock_out ?? $attendance->clock_out;

        $hasCorrection = !is_null($correction);

        $displayBreaks = collect($correction?->breaks ?? [])
            ->map(fn ($b) => (object) [
                'start' => $b['start'] ?? '',
                'end' => $b['end'] ?? '',
            ]);

        if ($displayBreaks->isEmpty()) {
            $displayBreaks = $attendance->breaks->map(fn ($b) => (object) [
                'start' => optional($b->break_start)->format('H:i'),
                'end' => optional($b->break_end)->format('H:i'),
            ]);
        }
        
        $canEdit = is_null($attendance->pendingCorrection);
        
        if ($canEdit) {
            $displayBreaks->push((object) [
                'start' => '',
                'end' => '',
            ]);
        }

        return view('admin.detail', compact(
            'attendance',
            'correction',
            'isPending',
            'canEdit',
            'date',
            'clockIn',
            'clockOut',
            'displayBreaks',
            'remark',
        ));
    }


    public function adminStore(Request $request, $attendanceId)
    {
        abort_if(auth()->user()->role !=='admin', 403);

        $attendance = Attendance::findOrFail($attendanceId);

        abort_if($attendance->pendingCorrection, 403);

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

        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'breaks' => $breaks,
        ]);

        $attendance->breaks()->delete();

        foreach ($breaks as $break) {
            $attendance->breaks()->create([
                'break_start' => Carbon::parse($attendance->date)
                    ->setTimeFromTimeString($break['start']),
                'break_end' => Carbon::parse($attendance->date)
                    ->setTimeFromTimeString($break['end']),
            ]);
        }

        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'breaks' => $breaks,
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return redirect()->route('admin.detail', $attendance->id);
    }

    // スタッフ一覧
    public function staffList()
    {
        $staffs = User::where('role', 'user')->get();

        return view('admin.staff-list', compact('staffs'));
    }

    // 各スタッフの月次勤怠一覧
    public function staffAttendanceList(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $month = $request->input('month')
            ? Carbon::createFromFormat('Y-m', $request->input('month'))
            : now();

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetWeen('date', [$start, $end])
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($a) => $a->date->format('Y-m-d'));

        $dates = CarbonPeriod::create($start, $end);

        $rows = collect($dates)->map(function ($date) use ($attendances) {
            $attendance = $attendances->get($date->format('Y-m-d'));

            return [
                'date' => $date,
                'attendance' => $attendance,
                'weekday' => ['日', '月', '火', '水', '木', '金', '土',][$date->dayOfWeek],
            ];
        });

        return view('admin.staff-attendance-list', compact('rows', 'month', 'user'));
    }
}
