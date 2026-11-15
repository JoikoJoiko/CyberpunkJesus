@extends('layout')
@section('title', 'Игроки')
@section('content')
<header class="page-head">
    <div><h1>Игроки<span>.</span></h1><p>Анкет в выборке: {{ $players->total() }}</p></div>
    <a class="button primary" href="{{ route('players.create') }}">+ Новая анкета</a>
</header>

<form class="panel filters" method="get" action="{{ route('players.index') }}">
    <label class="filter-search">Поиск<input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Ник, сервер или примечание"></label>
    <label>Сервер<select name="server"><option value="">Все серверы</option>@foreach($servers as $option)<option value="{{ $option }}" @selected($option === $server)>{{ $option }}</option>@endforeach</select></label>
    <label>Сортировка<select name="sort">
        @foreach(['created_at' => 'Дата добавления', 'nickname' => 'Ник', 'server' => 'Сервер', 'interviewed_on' => 'Дата опроса'] as $key => $label)
            <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
        @endforeach
        @if($questions->isNotEmpty())
            <optgroup label="По ответу на вопрос">
                @foreach($questions as $question)
                    <option value="question_{{ $question->id }}" @selected($sort === 'question_'.$question->id)>{{ $question->label }}</option>
                @endforeach
            </optgroup>
        @endif
    </select></label>
    <label>Порядок<select name="direction"><option value="asc" @selected($direction === 'asc')>По возрастанию</option><option value="desc" @selected($direction === 'desc')>По убыванию</option></select></label>
    <button class="button secondary">Применить</button>
</form>

<section class="panel" aria-label="Список игроков">
@if($players->isNotEmpty())
    <div class="table-scroll" tabindex="0" role="region" aria-label="Таблица игроков">
        <table>
            <thead><tr><th>Ник</th><th>Сервер</th><th>Дата опроса</th><th>Ответы</th>@if($sortQuestion)<th>{{ $sortQuestion->label }}</th>@endif<th><span class="sr-only">Действия</span></th></tr></thead>
            <tbody>
            @foreach($players as $player)
                <tr>
                    <td><a class="row-title" href="{{ route('players.edit', $player) }}">{{ $player->nickname ?? 'Без ника' }}</a>
                        @if($player->notes)<small class="row-note">{{ str($player->notes)->limit(85) }}</small>@endif
                    </td>
                    <td>{{ $player->server ?? '—' }}</td>
                    <td>{{ $player->interviewed_on?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ $player->answers_count }}</td>
                    @if($sortQuestion)<td>{{ $player->sorted_value !== null ? str($sortQuestion->displayAnswer($player->sorted_value))->limit(80) : '—' }}</td>@endif
                    <td><a class="link" href="{{ route('players.edit', $player) }}">Открыть →</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($players->hasPages())
        <nav class="pagination" aria-label="Страницы">
            @if($players->previousPageUrl())<a class="button secondary" href="{{ $players->previousPageUrl() }}">← Назад</a>@endif
            <span>{{ $players->currentPage() }} из {{ $players->lastPage() }}</span>
            @if($players->nextPageUrl())<a class="button secondary" href="{{ $players->nextPageUrl() }}">Дальше →</a>@endif
        </nav>
    @endif
@else
    <div class="empty">
        <span class="metric-icon" aria-hidden="true">▤</span><h2>{{ $search !== '' || $server !== '' ? 'Игроки не найдены' : 'Анкет пока нет' }}</h2>
        @if($search !== '' || $server !== '')<a class="link" href="{{ route('players.index') }}">Сбросить фильтры</a>
        @else<a class="link" href="{{ route('players.create') }}">Добавить первую анкету →</a>@endif
    </div>
@endif
</section>
@endsection
