@extends('layout')
@section('title', 'Аккаунты')
@section('content')
<header class="page-head"><div><h1>Аккаунты<span>.</span></h1><p>У всех аккаунтов есть доступ к общему архиву и его настройкам.</p></div></header>
<div class="dash-grid accounts-grid">
    <section class="panel section-panel">
        <header class="section-head"><h2>Участники</h2></header>
        @foreach($accounts as $account)
            <div class="recent-row">
                <span><strong>{{ $account->name }}</strong><small>{{ $account->email }}</small></span>
                @if($account->id === auth()->id())<span class="tag">Вы</span>@endif
            </div>
        @endforeach
    </section>
    <section class="panel section-panel">
        <header class="section-head"><h2>Новый аккаунт</h2></header>
        <form method="post" action="{{ route('accounts.store') }}" class="stack">
            @csrf
            <label>Имя<input type="text" name="name" maxlength="100" value="{{ old('name') }}" required></label>
            <label>Почта<input type="email" name="email" maxlength="254" value="{{ old('email') }}" required></label>
            <label>Пароль<input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required><small>Минимум 12 символов.</small></label>
            <label>Повторите пароль<input type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required></label>
            <div><button class="button primary">Создать аккаунт</button></div>
        </form>
    </section>
</div>
@endsection
