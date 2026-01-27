<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>
<body>
    @if(Auth::user()->role === 'admin')
        <header class="header">
            <div class="header__inner">
                <div class="header__utilities">
                    <a class="header__logo" href="/"><img src="{{ asset('images/COACHTECHヘッダーロゴ (1).png') }}" alt="COΛCHTECH"></a>
                    <nav>
                        <ul class="header-nav">

                            <li class="header-nav__item">
                                <a href="{{ route('admin.list') }}" class="attendance">勤怠一覧</a>
                            </li>
                            <li class="header-nav__item">
                                <a href="{{ route('admin.staffList') }}" class="attendance">スタッフ一覧</a>
                            </li>
                            <li class="header-nav__item">
                                <a href="{{ route('corrections.list') }}" class="attendance">申請一覧</a>
                            </li>
                            @auth
                            <li class="header-nav__item">
                                <form  action="/logout" method="post">
                                    @csrf
                                    <button class="header-nav__button">ログアウト</button>
                                </form>
                            </li>
                            @endauth
                        </ul>
                    </nav>
                </div>
            </div>
        </header>
    @else
        <header class="header">
            <div class="header__inner">
                <div class="header__utilities">
                    <a class="header__logo" href="/"><img src="{{ asset('images/COACHTECHヘッダーロゴ (1).png') }}" alt="COΛCHTECH"></a>
                    <nav>
                        <ul class="header-nav">

                            <li class="header-nav__item">
                                <a href="{{ route('generals.index') }}" class="attendance">勤怠</a>
                            </li>
                            <li class="header-nav__item">
                                <a href="{{ route('generals.list') }}" class="attendance">勤怠一覧</a>
                            </li>
                            <li class="header-nav__item">
                                <a href="{{ route('corrections.list') }}" class="attendance">申請</a>
                            </li>

                            @auth
                            <li class="header-nav__item">
                                <form  action="/logout" method="post">
                                    @csrf
                                    <button class="header-nav__button">ログアウト</button>
                                </form>
                            </li>
                            @endauth
                        </ul>
                    </nav>

                </div>
            </div>
        </header>
    @endif
    <main>
        @yield('content')
    </main>

    @stack('scripts')

</body>
</html>