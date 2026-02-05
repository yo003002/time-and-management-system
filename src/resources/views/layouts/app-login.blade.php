<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common-login.css') }}">
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <a class="header__logo" href="/"><img src="{{ asset('images/COACHTECHヘッダーロゴ (1).png') }}" alt="COΛCHTECH"></a>
        </div>
    </header>
    
    <main>
        @yield('content')
    </main>
</body>
</html>