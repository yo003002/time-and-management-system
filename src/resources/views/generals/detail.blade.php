<!-- 勤怠詳細画面　'/attendance/detail/{id} -->

@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/generals/detail.css') }}">
@endsection

@section('content')
<div class="detail-content">
    <div class="detail-header">
        <h1 class="header-title">勤怠詳細</h1>
    </div>
    <form action="{{ route('generals.correction.store', $attendance->id) }}" method="post" class="detail-form">
        @csrf
        <div class="detail-list">
            <div class="detail-table">
                <table class="detail-table__inner">
                    <tr class="detail-table__row">
                        <th>名前</th>
                        <td>
                            <div class="detail-name">{{ $attendance->user->name }}</td>
                        </div>
                    </tr class="detail-table__row">
                    <tr class="detail-table__row">
                        <th>日付</th>
                        <td>
                            <div class="detail-date">
                                <p>{{ $date->format('Y年')}}</p>
                                <p>{{ $date->format('n月j日')}}</p>
                            </div>
                        </td>
                    </tr>

                    <tr class="detail-table__row">
                        <th>出勤・退勤</th>
                        <td>
                            <div class="detail-attendance">
                                <input type="text" name="clock_in" class="time-input" value="{{ old('clock_in', optional($clockIn)->format('H:i')) }}"{{ $canEdit ? '' : 'disabled' }}>
                                
                                <span class="hyphen">～</span>
                                
                                <input type="text" name="clock_out" class="time-input" value="{{ old('clock_out', optional($clockOut)->format('H:i')) }}"{{ $canEdit ? '' : 'disabled' }}>
                            </div>
                            <div class="form__error">
                                @error('clock_in')
                                    {{ $message }}
                                @enderror

                                @error('clock_out')
                                    {{ $message }}
                                @enderror
                            </div>
                        </td>
                    </tr>

                    @foreach($displayBreaks as $i => $break)
                        <tr class="detail-table__row">
                            <th><p>休憩{{ $i === 0 ? '' : $i + 1 }}</p></th>
                            <td>
                                <div class="detail-break">
                                    <input type="text" name="breaks[{{ $i }}][start]" class="time-input"
                                    value="{{ old("breaks.$i.start", $break->start) }}"{{ $canEdit ? '' : 'disabled'}}>
                                    
                                    <span class="hyphen">～</span>

                                    <input type="text" name="breaks[{{ $i }}][end]" class="time-input" value="{{ old("breaks.$i.end", $break->end) }}"{{ $canEdit ? '' : 'disabled' }}>
                                </div>
                                <div class="form__error">
                                    @error("breaks.$i.start")
                                        {{ $message }}
                                    @enderror
                                    @error("breaks.$i.end")
                                        {{ $message }}
                                    @enderror
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    <tr class="detail-table__row">
                        <th>備考</th>
                        <td>
                        <textarea name="remark" {{ $canEdit ? '' : 'disabled' }}>{{ $remark }}</textarea>
                        <div class="form__error">
                            @error('remark')
                                {{ $message }}
                            @enderror
                        </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if(! $canEdit)
        <div class="waiting-message">
            @if($isPending)
                <p>*承認待ちのため修正はできません。</p>
            @else
                <p>*承認済みの申請は修正できません。</p>
            @endif
        </div>
        @endif

        @if($canEdit)
            <div class="detail-button">
                <button type="submit" class="detail-button__submit">修正</button>
            </div>
        @endif
    </form>
</div>
@endsection