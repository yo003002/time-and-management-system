<!-- 勤怠一覧画面（管理者） -->

@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/list.css') }}">
@endsection

@section('content')
<div class="admin-list-content">
    <div class="admin-list-header">
        <h1 class="admin-header-title">{{ $date->format('Y年n月j日') }}の勤怠</h1>
    </div>
    <div class="date">
        <div class="date-swith">
            <a href="{{ route('admin.list', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}"><span class="arrow">←</span>前日</a>

            <span class="now-date">
                <img class="date-logo" src="{{ asset('images/icon_125550.svg') }}" alt="カレンダーロゴ">
                {{ $date->format('Y/m/d') }}
            </span>

            <a href="{{ route('admin.list', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}">翌日<span class="arrow">→</span></a>
        </div>
    </div>
    <div class="admin-list-inner">
        <div class="admin-list-table">
            <table class="admin-list-table__inner">
                <tr class="admin-list-table__row">
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>

                @foreach ($users as $user)
                    <tr class="admin-list-table__row">
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->attendance?->clock_in?->format('H:i') }}</td>
                        <td>{{ $user->attendance?->clock_out?->format('H:i') }}</td>
                        <td>{{ $user->attendance?->break_time_formatted }}</td>
                        <td>{{ $user->attendance?->working_time_formatted }}</td>
                        <td>
                            @if($user->attendance)
                                <a href="{{ route('admin.detail', $user->attendance->id) }}">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
@endsection