<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\AttendanceBreak;
use App\Models\User;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'work_status',
    ];

    
    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    // 申請中
    public function pendingCorrection()
    {
        return $this->hasOne(AttendanceCorrection::class)
            ->where('status', 'pending')
            ->latest();
    }

    // 承認済み
    public function approvedCorrection()
    {
        return $this->hasOne(AttendanceCorrection::class)
            ->where('status', 'approved')
            ->latest();
    }

    // 実働時間（計算用）
    public function getWorkingMinutesAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return null;
        }
        
        $totalMinutes = $this->clock_in->diffInMinutes($this->clock_out);

        $breakMinutes = $this->breaks->sum(function ($break) {
            if (!$break->break_end) {
                return 0;
            }
            return $break->break_start->diffInMinutes($break->break_end);
        });

        return $totalMinutes - $breakMinutes;
    }

    // 休憩の合計時間（計算用）
    public function getBreakMinutesAttribute()
    {
        return $this->breaks->sum(function ($break) {
            if (!$break->break_end) {
                return 0;
            }
            return $break->break_start->diffInMinutes($break->break_end);
        });
    }

    // 休憩表示用
    public function getBreakTimeFormattedAttribute()
    {
        if ($this->break_minutes === 0) {
            return null;
        }
        
        return sprintf(
            '%d:%02d',
            floor($this->break_minutes / 60),
            $this->break_minutes % 60
        );
    }

    // 実働表示用
    public function getWorkingTimeFormattedAttribute()
    {
        if (!$this->working_minutes) {
            return null;
        }
        
        return sprintf(
            '%d:%02d',
            floor($this->working_minutes / 60),
            $this->working_minutes % 60
        );
    }
}
