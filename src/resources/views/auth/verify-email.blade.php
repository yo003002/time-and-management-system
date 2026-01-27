<!-- メール認証誘導画面 -->
@extends('layouts.app-login')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-content">
    <div class="verify-message">
        <h3>
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </h3>
    </div>
    <div class="verify-resend">
        <p>認証はこちらから</p>
    </div>
    <div class="resend-massage">
        <form method="post" action="{{ route('verification.send') }}" class="verify-form">
            @csrf
            <button type="submit" class="verify-form__button">
                認証メールを再送する
            </button>
        </form>
    </div>
</div>
@endsection