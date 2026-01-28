<!-- 修正申請承認画面（管理者）　'/stamp_correction_request/approve/{attendance_correct_request_id}' -->

@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/corrections/approve.css') }}">
@endsection

@section('content')
<div class="approve-detail-content">
    <div class="approve-detail-header">
        <h1 class="header-title">勤怠詳細</h1>
    </div>
    <form action="{{ route('correction.approve', $correction->id) }}" method="post" class="detail-form">
        @csrf
        <div class="approve-detail-list">
            <div class="approve-detail-table">
                <table class="approve-detail-table__inner">
                    <tr class="approve-detail-table__row">
                        <th>名前</th>
                        <td>
                            <div class="approve-detail-name">{{ $correction->attendance->user->name }}</td>
                        </div>
                    </tr class="approve-detail-table__row">
                    <tr class="approve-detail-table__row">
                        <th>日付</th>
                        <td>
                            <div class="approve-detail-date">
                                <p>{{ $correction->attendance->date->format('Y年n年j日')}}</p>
                            </div>
                        </td>
                    </tr>

                    <tr class="approve-detail-table__row">
                        <th>出勤・退勤</th>
                        <td>
                            <div class="approve-detail-attendance">
                                <p>
                                    {{ optional($correction->clock_in)->format('H:i') }}
                                </p>
                                <span class="hyphen">～</span>
                                <p>
                                    {{ optional($correction->clock_out)->format('H:i') }}
                                </p>
                            </div>
                        </td>
                    </tr>

                        @foreach($correction->breaks as $i => $break)
                            <tr class="approve-detail-table__row">
                                <th><p>休憩{{ $i + 1 }}</p></th>
                                <td>
                                    <div class="approve-detail-break">
                                        {{ $break['start'] ?? '' }}
                                        <span class="hyphen">～</span>
                                        {{ $break['end'] ?? '' }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                    <tr class="approve-detail-table__row">
                        <th>備考</th>
                        <td>
                            <p class="remark">{{ $correction->remark ?: '' }}</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="approve-detail-button">
            @if($correction->status === 'pending')
                <button type="submit" class="detail-button__submit">承認</button>
            @else
                <button class="detail-button__disabled" disabled>承認済み</button>
            @endif
        </div>
    </form>
</div>
@endsection