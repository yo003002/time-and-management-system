<!-- 勤怠一覧　/attendance/list -->

@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/generals/list.css') }}">
@endsection

@section('content')
<div class="list-content">
    <div class="list-header">
        <h1 class="header-title">勤怠一覧</h1>
    </div>
    <div class="month">
        <div class="month-swith">
            <a href="{{ route('generals.list', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}"><span class="arrow">←</span>前月</a>

            <span class="now-month">
                <img class="month-logo" src="{{ asset('images/icon_125550.svg') }}" alt="カレンダーロゴ">{{ $month->format('Y/m') }}
            </span>

            <a href="{{ route('generals.list', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}">翌月<span class="arrow">→</span></a>
        </div>
    </div>
    <div class="list-inner">
        <div class="list-table">
            <table class="list-table__inner">
                <tr class="list-table__row">
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
                <tr class="list-table__row">
                    @foreach($attendances as $attendance)
                        <td>{{ $attendance->date->format('m/d') }}({{ ['日','月','火','水','木','金','土'][$attendance->date->dayOfWeek] }})</td>
                        <td>{{ optional($attendance->clock_in)->format('H:i') }}</td>
                        <td>{{ optional($attendance->clock_out)->format('H:i') }}</td>
                        <td>
                            @php
                                $breakMinutes = 0;

                                foreach ($attendance->breaks as $break) {
                                    if ($break->break_end) {
                                        $breakMinutes +=$break->break_start->diffInMinutes($break->break_end);
                                    }
                                }
                                $breakHours = floor($breakMinutes / 60);
                                $breakMins = $breakMinutes % 60;
                            @endphp

                            {{ sprintf('%d:%02d', $breakHours, $breakMins) }}
                        </td>
                        <td>
                            @if($attendance->clock_in && $attendance->clock_out)
                                @php
                                    $workMinutes = $attendance->clock_in->diffInMinutes($attendance->clock_out);
                                    $netMinutes = $workMinutes - $breakMinutes;

                                    $workHours = floor($netMinutes / 60);
                                    $workMins = $netMinutes % 60;
                                @endphp

                                {{ sprintf('%d:%02d', $workHours, $workMins) }}
                            @endif
                        </td>
                        <td><a href="">詳細</a></td>
                    @endforeach
                </tr>
            </table>
        </div>
    </div>
</div>
@endsection