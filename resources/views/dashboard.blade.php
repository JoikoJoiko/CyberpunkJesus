@extends('layout')
@section('title', 'Обзор')
@section('content')
<section class="hero" aria-labelledby="dashboard-title">
    <div class="hero-copy">
        <h1 id="dashboard-title">Грехометр<span>.</span></h1>
        <p>Анкеты игроков, серверы и статистика ответов в одном архиве.</p>
        <a class="button primary" href="{{ route('players.create') }}"><span aria-hidden="true">+</span> Новая анкета</a>
    </div>
</section>

<div class="metrics">
    @foreach([
        ['◉', 'Анкеты', $playerCount, route('players.index')],
        ['▥', 'Серверы', $serverCount, route('players.index')],
        ['☷', 'Вопросы', $questionCount, route('questions.index')],
        ['◩', 'Ответы', $answerCount, route('reports.answers')],
    ] as [$icon, $label, $total, $href])
        <a class="metric panel" href="{{ $href }}">
            <span class="metric-head"><span class="metric-icon" aria-hidden="true">{{ $icon }}</span>{{ $label }}</span>
            <strong>{{ number_format($total, 0, ',', ' ') }}</strong>
        </a>
    @endforeach
</div>

<div class="dash-grid">
    <section class="panel section-panel">
        <header class="section-head"><div><h2>Новые анкеты</h2><p>За последние 14 дней</p></div></header>
        @php($max = max(1, $activity->max('count')))
        <div class="activity-chart" aria-label="Количество добавленных анкет по дням">
            @foreach($activity as $day)
                <div class="chart-column" title="{{ $day['label'] }} — {{ $day['count'] }}">
                    <div class="chart-track" aria-hidden="true"><span style="--percent: {{ 100 * $day['count'] / $max }}%"></span></div>
                    <span class="sr-only">{{ $day['label'] }} — {{ $day['count'] }}</span>
                    @if($loop->even || $loop->first) <small aria-hidden="true">{{ $day['label'] }}</small> @else <small aria-hidden="true">&nbsp;</small> @endif
                </div>
            @endforeach
        </div>
    </section>
    <section class="panel section-panel">
        <header class="section-head"><div><h2>Серверы</h2><p>По количеству анкет</p></div></header>
        @forelse($servers as $item)
            <div class="bar-row">
                <div><span>{{ $item->server }}</span><strong>{{ $item->total }}</strong></div>
                <div class="bar-track" aria-hidden="true"><span style="--percent: {{ 100 * $item->total / $servers->first()->total }}%"></span></div>
            </div>
        @empty
            <div class="empty compact"><span class="metric-icon" aria-hidden="true">▥</span><p>Укажите сервер в анкете, чтобы он появился здесь.</p></div>
        @endforelse
    </section>
</div>

<section class="panel section-panel recent">
    <header class="section-head"><h2>Последние игроки</h2><a class="link" href="{{ route('players.index') }}">Все анкеты ↗</a></header>
    @forelse($recent as $player)
        <a class="recent-row" href="{{ route('players.edit', $player) }}">
            <span><strong>{{ $player->nickname ?? 'Без ника' }}</strong><small>{{ $player->server ?? 'Сервер не указан' }}</small></span>
            <span>{{ $player->answers_count }} отв. <span aria-hidden="true">→</span></span>
        </a>
    @empty
        <div class="empty compact"><p>Пока ни одной анкеты.</p><a class="link" href="{{ route('players.create') }}">Добавить первую</a></div>
    @endforelse
</section>
@endsection
