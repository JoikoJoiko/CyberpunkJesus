@extends('layout')
@section('title', 'Игроки')
@section('content')
<header class="page-head">
    <div><h1>Игроки<span>.</span></h1><p>Анкет в выборке: {{ $players->total() }}</p></div>
    <a class="button primary" href="{{ route('players.create') }}">+ Новая анкета</a>
</header>

<form class="panel filters" method="get" action="{{ route('players.index') }}">
    <label>Поиск<input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Ник или примечание"></label>
    <button class="button secondary">Найти</button>
</form>

<section class="panel" aria-label="Список игроков">
@if($players->isNotEmpty())
    <div class="table-scroll" tabindex="0" role="region" aria-label="Таблица игроков">
        <table>
            <thead><tr><th>Ник</th><th>Возраст</th><th>Дата опроса</th><th>Ответы</th><th><span class="sr-only">Действия</span></th></tr></thead>
            <tbody>
            @foreach($players as $player)
                <tr>
                    <td><a class="row-title" href="{{ route('players.show', $player) }}">{{ $player->nickname ?? 'Без ника' }}</a>
                        @if($player->notes)<small class="row-note">{{ str($player->notes)->limit(85) }}</small>@endif
                    </td>
                    <td>{{ \App\Models\Player::ageFrom($player->birth_date) ?? '—' }}</td>
                    <td>{{ $player->interviewed_on?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $player->answers_count }}</td>
                    <td><a class="link" href="{{ route('players.show', $player) }}">Открыть →</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['pages' => $players])
@else
    <div class="empty">
        <span class="metric-icon" aria-hidden="true">▤</span><h2>{{ $search !== '' ? 'Игроки не найдены' : 'Анкет пока нет' }}</h2>
        @if($search !== '')<a class="link" href="{{ route('players.index') }}">Сбросить поиск</a>
        @else<a class="link" href="{{ route('players.create') }}">Добавить первую анкету →</a>@endif
    </div>
@endif
</section>
@endsection
