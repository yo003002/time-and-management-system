<!-- スタッフ一覧（管理者）　/admin/staff/list -->

@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff-list.css') }}">
@endsection

@section('content')
<div class="staff-list-content">
    <div class="staff-list-header">
        <h1 class="staff-list-header__title">スタッフ一覧</h1>
    </div>

    <div class="staff-list-inner">
        <div class="staff-list-table">
            <table class="staff-list-table__inner">
                <tr class="staff-list-table__row">
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>

                @foreach($staffs as $staff)
                    <tr class="staff-list-table__row">
                        <td>{{ $staff->name }}</td>
                        <td>{{ $staff->email }}</td>
                        <td>
                            <a href="{{ route('admin.staffAttendanceList', $staff->id) }}">詳細</a>
                        </td>
                    </tr>
                @endforeach

            </table>
        </div>
    </div>
</div>
@endsection