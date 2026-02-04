<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\AdminAttendanceRequest;
use App\Services\AttendanceCorrectionService;
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
        $now = now();

        $attendance = Attendance::where('user_id', auth()->id())
            ->where('date', $now->toDateString())
            ->first();

        return view('generals.index', [
            'attendance' => $attendance,
            'today' => $now,
            'currentTime' => $now->format('H:i'),
        ]);
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

        return redirect()->route('generals.index');
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

        return redirect()->route('generals.index');
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

        return redirect()->route('generals.index');
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

        return redirect()->route('generals.index');
    }

    // その月の勤怠一覧（一般）
    public function list(Request $request)
    {
        $user = auth()->user();

        // 月を表示
        $month = $request->input('month')
        ? CarbonImmutable::createFromFormat('!Y-m', $request->input('month'))
        : CarbonImmutable::now()->startOfMonth();

        $start = $month;
        $end = $month->endOfMonth();
        
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

    // 勤怠詳細（一般）
    public function detail(Request $request, $id)
    {
        $attendance = Attendance::with([
            'breaks',
            'approvedCorrection',
            'pendingCorrection',
            'user',
        ])->findOrFail($id);

        // 自分の勤怠のみ確認できる
        abort_if($attendance->user_id !== auth()->id(), 403);

        $fromApprovedList = $request->query('from') === 'approved';

        $correction = 
            $attendance->pendingCorrection
            ?? $attendance->approvedCorrection;

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

        $canEdit = 
            is_null($attendance->pendingCorrection)
            && ! $fromApprovedList;
        
        if ($canEdit) {
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
            'canEdit'
        ));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttendanceRequest $request, $attendanceId)
    {
        $attendance = Attendance::findOrFail($attendanceId);

        abort_if($attendance->user_id !== auth()->id(), 403);

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

        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'requested_by' => auth()->id(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'breaks' => empty($breaks) ? null : $breaks,
            'remark' => $request->remark,
            'status' => 'pending',
        ]);

        return redirect()->route('generals.detail', $attendance->id);
    }


    // 申請一覧（一般・管理者共通）
    public function correctionList(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = AttendanceCorrection::with('attendance.user')
            ->where('status', $status);

        if (! auth()->user()->can('admin')) {
            $query->whereHas('attendance', function ($q) {
                $q->where('user_id', auth()->id());
            });

            if ($status === 'approved') {
                $query->where('requested_by', auth()->id());
            }
        }

        $corrections = $query->latest()->get();

        $corrections->each(function ($correction) {
            if (auth()->user()->can('admin')) {
                $correction->detail_url = route('correction.approve.form', $correction->id);
            } else {
                $params = ['id' => $correction->attendance->id];

                if ($correction->status === 'approved') {
                    $params['from'] = 'approved';
                }

                $correction->detail_url = route('generals.detail', $params);
            }
        });

        return view('corrections.list', compact('corrections', 'status'));
    }



    // ～以降管理者～

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


    public function adminStore(AdminAttendanceRequest $request, $attendanceId)
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

        $breaksForSave = empty($breaks) ? null : $breaks;

        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'breaks' => $breaksForSave,
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
            'requested_by' => auth()->id(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'breaks' => $breaksForSave,
            'remark' => $request->remark,
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
            ? CarbonImmutable::createFromFormat('!Y-m', $request->input('month'))
            : CarbonImmutable::now()->startOfMonth();

        $start = $month;
        $end = $month->endOfMonth();

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

    // 各スタッフの月次勤怠一覧のCSV出力
    public function exportStaffMonthly(Request $request, $id)
    {
        $month = $request->query('month');
        [$year, $monthNumber] = explode('-', $month);

        $user = User::findOrFail($id);

        $attendances = Attendance::with('breaks')
            ->where('user_id', $id)
            ->whereYear('date', $year)
            ->whereMonth('date', $monthNumber)
            ->orderBy('date')
            ->get();

        $fileName = "attendance_{$user->name}_{$month}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        $callback = function () use ($attendances, $user) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '日付',
                '出勤',
                '退勤',
                '休憩',
                '合計',
            ]);

            $weekdays = ['日', '月', '火', '水', '木', '金', '土',];

            foreach ($attendances as $attendance) {
                $breakMinutes = $attendance->breaks->sum(function ($break) {
                    if (!$break->break_end) {
                        return 0;
                    }

                    return Carbon::parse($break->break_start)
                        ->diffInMinutes(Carbon::parse($break->break_end));
                });

                $workingMinutes = null;

                if ($attendance->clock_in && $attendance->clock_out) {
                    $workingMinutes = Carbon::parse($attendance->clock_in)
                        ->diffInMinutes(Carbon::parse($attendance->clock_out))
                        - $breakMinutes;
                }

                $dateFormatted = $attendance->date->format('Y/m/d');
                $weekday = $weekdays[$attendance->date->dayOfWeek];
                $fullDate = "{$dateFormatted}({$weekday})";

                fputcsv($handle, [
                    $fullDate,
                    optional($attendance->clock_in)?->format('H:i'),
                    optional($attendance->clock_out)?->format('H:i'),
                    gmdate('H:i', $breakMinutes * 60),
                    $workingMinutes !== null ? gmdate('H:i', $workingMinutes * 60) : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // 修正申請承認画面表示
    public function approveForm($id)
    {
        $correction = AttendanceCorrection::with('attendance.user')
            ->findOrFail($id);

        return view('corrections.approve', compact('correction'));
    }

    // 承認機能
    public function approve($id, AttendanceCorrectionService $service)
    {
        $correction = AttendanceCorrection::with('attendance')
            ->findOrFail($id);

        if ($correction->status === 'approved') {
            return back();
        }

        $service->approve(
            $correction,
            auth()->user()
        );

        return redirect()
            ->route('correction.approve.form', $correction->id);
    }
}
