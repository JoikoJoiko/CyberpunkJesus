<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>@yield('title', 'Обзор') · Грехометр</title>
    <link rel="stylesheet" href="{{ asset('assets/dossier.css') }}">
    <script src="{{ asset('assets/dossier.js') }}" defer></script>
</head>
<body class="@guest guest @endguest">
@auth
    <a class="skip-link" href="#main">К содержимому</a>
    <div class="shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark" aria-hidden="true">✝</span><span>ГРЕХОМЕТР<small>АРХИВ ОПРОСОВ</small></span></a>
            <nav class="nav" aria-label="Разделы">
                <a @class(['nav-link', 'active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif><span aria-hidden="true">◫</span> Обзор</a>
                <a @class(['nav-link', 'active' => request()->routeIs('players.*')]) href="{{ route('players.index') }}" @if(request()->routeIs('players.*')) aria-current="page" @endif><span aria-hidden="true">▤</span> Игроки</a>
                <a @class(['nav-link', 'active' => request()->routeIs('questions.*')]) href="{{ route('questions.index') }}" @if(request()->routeIs('questions.*')) aria-current="page" @endif><span aria-hidden="true">☷</span> Вопросы</a>
                <a @class(['nav-link', 'active' => request()->routeIs('reports.*')]) href="{{ route('reports.answers') }}" @if(request()->routeIs('reports.*')) aria-current="page" @endif><span aria-hidden="true">◩</span> Ответы</a>
                <a @class(['nav-link', 'active' => request()->routeIs('history.*')]) href="{{ route('history.index') }}" @if(request()->routeIs('history.*')) aria-current="page" @endif><span aria-hidden="true">◷</span> История</a>
            </nav>
            <div class="session">
                <a class="avatar" href="{{ route('profile.edit') }}" aria-label="Изменить аватар" title="Изменить аватар">
                    @if(\Illuminate\Support\Facades\Storage::disk('local')->exists(auth()->user()->avatarPath()))
                        <img src="{{ route('avatar.show') }}" alt="">
                    @else
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </a>
                <div class="session-user"><strong>{{ auth()->user()->name }}</strong>
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="signout">Выйти ↗</button></form>
                </div>
            </div>
        </aside>
        <main class="content" id="main">
            @if(session('status')) <div class="notice" role="status">{{ session('status') }}</div> @endif
            @include('partials.errors')
            @yield('content')
        </main>
    </div>
@else
    @yield('content')
@endauth
    <dialog data-confirm-dialog>
        <form method="dialog">
            <h2 data-confirm-title tabindex="-1">Подтвердите удаление</h2>
            <p data-confirm-message></p>
            <div class="actions">
                <button class="button secondary" value="cancel" autofocus>Отмена</button>
                <button class="button danger-fill" value="delete">Удалить</button>
            </div>
        </form>
    </dialog>
</body>
</html>
