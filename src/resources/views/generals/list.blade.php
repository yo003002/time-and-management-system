<!-- 勤怠一覧（一般）　/attendance/list -->

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

                @foreach($rows as $row)
                    <tr class="list-table__row">
                        <td>{{ $row['date']->format('m/d') }}({{ $row['weekday'] }})</td>
                        <td>{{ $row['attendance']?->clock_in?->format('H:i') }}</td>
                        <td>{{ $row['attendance']?->clock_out?->format('H:i') }}</td>
                        <td>{{ $row['attendance']?->break_time_formatted }}</td>
                        <td>{{ $row['attendance']?->working_time_formatted }}</td>
                        <td>
                            @if($row['attendance'])
                                <a href="{{ route('generals.detail', $row['attendance']->id) }}">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
@endsection