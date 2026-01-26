<!-- 申請一覧（一般・管理者共通）　/stamp_correction_requst/list -->

@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/corrections/list.css') }}">
@endsection

@section('content')
<div class="corrections-list-content">
    <div class="corrections-list-header">
        <h1 class="corrections-header-title">申請一覧</h1>
    </div>

    <div class="approve-btn">
        <div class="approve-btn-list">
            <a href="{{ route('corrections.list', ['status' => 'pending']) }}"
            class="{{ $status === 'pending' ? 'tab active' : 'tab' }}">
                承認待ち
            </a>
        </div>
        <div class="approve-btn-list">
            <a href="{{ route('corrections.list', ['status' => 'approved']) }}" 
            class="{{ $status === 'approved' ? 'tab active' : 'tab' }}">
                承認済み
            </a>
        </div>
    </div>

    <div class="corrections-list-inner">
        <div class="corrections-list-table">
            <table class="corrections-list-table__inner">
                <tr class="corrections-list-table__row">
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>

                @foreach($corrections as $correction)
                    <tr class="corrections-list-table__row">
                        <td>{{ $correction->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
                        <td>{{ $correction->attendance->user->name }}</td>
                        <td>{{ $correction->attendance->date->format('Y/m/d') }}</td>
                        <td>{{ $correction->remark }}</td>
                        <td>{{ $correction->created_at->format('Y/m/d') }}</td>
                        <td>
                            <a href="{{ $correction->detail_url }}">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
@endsection