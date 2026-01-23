<!-- 勤怠詳細画面（管理者）　'/admin/attendance/{id} -->

@extends('layouts.app-admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/detail.css') }}">
@endsection

@section('content')
<div class="admin-detail-content">
    <div class="admin-detail-header">
        <h1 class="admin-header-title">勤怠詳細</h1>
    </div>
    <form action="{{ route('admin.correction.store', $attendance->id) }}" method="post" class="admin-detail-form">
        @csrf
        <div class="admin-detail-list">
            <div class="admin-detail-table">
                <table class="admin-detail-table__inner">
                    <tr class="admin-detail-table__row">
                        <th>名前</th>
                        <td>
                            <div class="admin-detail-name">{{ $attendance->user->name }}</td>
                        </div>
                    </tr class="admin-detail-table__row">
                    <tr class="admin-detail-table__row">
                        <th>日付</th>
                        <td>
                            <div class="admin-detail-date">
                                <p>{{ $date->format('Y年')}}</p>
                                <p>{{ $date->format('n月j日')}}</p>
                            </div>
                        </td>
                    </tr>

                    <tr class="admin-detail-table__row">
                        <th>出勤・退勤</th>
                        <td>
                            <div class="admin-detail-attendance">
                                <input type="text" name="clock_in" class="time-input" value="{{ optional($clockIn)->format('H:i') }}"{{ $isPending ? 'disabled' : '' }}>
                                
                                <span class="hyphen">～</span>
                                <input type="text" name="clock_out" class="time-input" value="{{ optional($clockOut)->format('H:i') }}"{{ $isPending ? 'disabled' : '' }}>
                                
                            </div>
                        </td>
                    </tr>

                    @foreach($displayBreaks as $i => $break)
                        <tr class="admin-detail-table__row">
                            <th><p>休憩{{ $i === 0 ? '' : $i + 1 }}</p></th>
                            <td>
                                <div class="admin-detail-break">
                                    <input type="text" name="breaks[{{ $i }}][start]" class="time-input"
                                    value="{{ $break->start }}"{{ $isPending ? 'disabled' : '' }}>
                                    
                                    <span class="hyphen">～</span>

                                    <input type="text" name="breaks[{{ $i }}][end]" class="time-input" value="{{ $break->end }}"{{ $isPending ? 'disabled' : '' }}>
                                    
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    <tr class="admin-detail-table__row">
                        <th>備考</th>
                        <td>
                        <textarea name="remark" {{ $isPending ? 'disabled' : '' }}>{{ $remark }}</textarea>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        @if($isPending)
            <div class="waiting-message">
                <p>*承認待ちのため修正はできません。</p>
            </div>
        @endif

        @if(!($isPending))
            <div class="admin-detail-button">
                <button type="submit" class="admin-detail-button__submit">修正</button>
            </div>
        @endif
    </form>
</div>
@endsection