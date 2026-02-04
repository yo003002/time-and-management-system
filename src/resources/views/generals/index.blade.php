<!-- 勤怠 /attendance -->

@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/generals/index.css') }}">
@endsection

@section('content')
<div class="attendance-content">
    <div class="attendance-inner">
        <div class="various">
            <div class="status">
                <div class="status-content">
                    @if(!$attendance || $attendance->work_status === 'before_work')
                        <p>勤務外</p>
                    @elseif($attendance->work_status === 'working')
                        <p>出勤中</p>
                    @elseif($attendance->work_status === 'on_break')
                        <p>休憩中</p>
                    @elseif($attendance->work_status === 'finished')
                        <p>退勤済</p>
                    @endif
                </div>
            </div>
            <div class="date">
                {{ $today->format('Y年n月j日') }}({{ ['日','月','火','水','木','金','土'][$today->dayOfWeek] }})
            </div>
            <div class="time">
                <span id="realtime">{{ $currentTime }}</span>
            </div>
            <div class="status-button">
                <div class="status-button__content">
                    @if(!$attendance || $attendance->work_status === 'before_work')
                        <form method="post" action="{{ route('generals.clockIn') }}" >
                            @csrf
                            <button type="submit" class="status-button__clock-in">出勤</button>
                        </form>
                    @endif

                    @if($attendance && $attendance->work_status === 'working')
                        <form method="post" action="{{ route('generals.clockOut') }}" >
                            @csrf
                            <button type="submit" class="status-button__clock-out">退勤</button>
                        </form>
                    @endif

                    @if($attendance && $attendance->work_status === 'working')
                        <form method="post" action="{{ route('generals.breakIn') }}" >
                            @csrf
                            <button type="submit" class="status-button__break-in">休憩入</button>
                        </form>
                    @endif

                    @if($attendance && $attendance->work_status === 'on_break')
                        <form method="post" action="{{ route('generals.breakOut') }}" >
                            @csrf
                            <button type="submit" class="status-button__break-out">休憩戻</button>
                        </form>
                    @endif

                    @if($attendance && $attendance->work_status === 'finished')
                        <p>お疲れ様でした。</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        document.getElementById('realtime').textContent = `${hours}:${minutes}`;
    }

    updateClock();

    setInterval(updateClock, 1000);
</script>
@endpush