@extends('layout')
@section('title', 'Вход')
@section('content')
<main class="login-shell">
    <div class="login-art" aria-hidden="true"></div>
    <section class="login-side" aria-labelledby="login-title">
        <div class="login-card">
            <div class="brand"><span class="brand-mark" aria-hidden="true">✝</span><span>ГРЕХОМЕТР<small>АРХИВ ОПРОСОВ</small></span></div>
            <h1 id="login-title">Вход в архив</h1>
            @include('partials.errors')
            <form method="post" action="{{ route('login.store') }}" class="stack">
                @csrf
                <label>Почта<input name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus></label>
                <label>Пароль<input name="password" type="password" autocomplete="current-password" required></label>
                <button class="button primary">Войти <span aria-hidden="true">↗</span></button>
            </form>
        </div>
    </section>
</main>
@endsection
