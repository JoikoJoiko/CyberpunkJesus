@extends('layout')
@section('title', 'Статистика ответов')
@section('content')
<header class="page-head"><div><h1>Статистика ответов<span>.</span></h1><p>Выберите вопрос, чтобы посмотреть распределение ответов.</p></div></header>
@if($selected)
    <div class="report-grid">
        <nav class="panel section-panel report-nav" aria-label="Вопросы для статистики">
            <header class="section-head"><h2>Вопросы</h2></header>
            @foreach($questions as $question)
                <a @class(['report-link', 'active' => $question->is($selected)]) href="{{ route('reports.answers', $question) }}" @if($question->is($selected)) aria-current="page" @endif><span>{{ $question->label }}</span><small>{{ $question->answers_count }}</small></a>
            @endforeach
        </nav>
        <div class="stack">
            <section class="panel section-panel">
                <header class="section-head"><div><h2>{{ $selected->label }}</h2><p>{{ $selected->type->label() }}</p></div></header>
                @php($coverage = $playerCount ? round(100 * $selected->answers_count / $playerCount) : 0)
                <div class="coverage"><strong>{{ $coverage }}%</strong><span>Заполнено {{ $selected->answers_count }} из {{ $playerCount }} анкет</span></div>
                <div class="bar-track" aria-hidden="true"><span style="--percent: {{ $coverage }}%"></span></div>
                @if($summary)
                    <dl class="summary-grid">
                        <div><dt>Минимум</dt><dd>{{ $summary->minimum }}</dd></div>
                        @if(isset($summary->average))<div><dt>Среднее</dt><dd>{{ round($summary->average, 3) }}</dd></div>@endif
                        <div><dt>Максимум</dt><dd>{{ $summary->maximum }}</dd></div>
                    </dl>
                @endif
            </section>
            <section class="panel section-panel">
                <header class="section-head"><div><h2>Частые ответы</h2><p>До 8 самых популярных вариантов</p></div></header>
                @forelse($distribution as $item)
                    <div class="bar-row">
                        <div><span>{{ str($selected->displayAnswer($item->value))->limit(140) }}</span><strong>{{ $item->total }}</strong></div>
                        <div class="bar-track" aria-hidden="true"><span style="--percent: {{ 100 * $item->total / $distribution->first()->total }}%"></span></div>
                    </div>
                @empty
                    <div class="empty compact"><p>На этот вопрос ещё никто не ответил.</p></div>
                @endforelse
            </section>
        </div>
    </div>
@else
    <div class="panel empty"><span class="metric-icon" aria-hidden="true">◩</span><h2>Вопросов пока нет</h2><a class="link" href="{{ route('questions.create') }}">Добавить вопрос →</a></div>
@endif
@endsection
