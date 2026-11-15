@extends('layout')
@section('title', 'Вопросы')
@section('content')
<header class="page-head">
    <div><h1>Вопросы<span>.</span></h1><p>Этот список задаёт поля во всех анкетах.</p></div>
    <a class="button primary" href="{{ route('questions.create') }}">+ Новый вопрос</a>
</header>
<div class="stack">
@if($blocks->isNotEmpty())
    <section class="panel section-panel">
        <details>
            <summary>Порядок блоков</summary>
            <form class="stack" method="post" action="{{ route('questions.blocks.order') }}">
                @csrf @method('PUT')
                <p class="row-note">Блоки с меньшим номером отображаются раньше.</p>
                <div class="field-grid">
                    @foreach($blocks as $block)
                        <label>{{ $block->name === '' ? 'Без блока' : $block->name }}<input type="number" name="positions[{{ $block->id }}]" value="{{ $block->position }}" min="0" max="99999" required></label>
                    @endforeach
                </div>
                <div><button class="button secondary">Сохранить порядок блоков</button></div>
            </form>
        </details>
    </section>
@endif
@forelse($groups as $name => $questions)
    <section class="panel section-panel">
        <header class="section-head">
            <h2>{{ $name === '' ? 'Без блока' : $name }}</h2>
            <a class="link" href="{{ route('questions.create', ['block' => $name]) }}">+ Вопрос в этот блок</a>
        </header>
        @php($block = $blocks->first(fn ($block) => $block->name === (string) $name))
        <form method="post" action="{{ route('questions.blocks.questions', $block) }}">
        @csrf @method('PUT')
        <div class="table-scroll" tabindex="0" role="region" aria-label="Таблица вопросов">
            <table>
                <thead><tr><th>Вопрос</th><th>Тип ответа</th><th>Порядок</th><th>Ответы</th><th><span class="sr-only">Действия</span></th></tr></thead>
                <tbody>
                @foreach($questions as $question)
                    <tr>
                        <td><a class="row-title" href="{{ route('questions.edit', $question) }}">{{ $question->label }}</a>
                            <small class="row-note"><code>{{ $question->code }}</code>@if($question->block) · {{ $question->block }}@endif</small>
                        </td>
                        <td><span class="tag">{{ $question->type->label() }}</span></td>
                        <td><input class="order-input" aria-label="Порядок вопроса {{ $question->label }}" type="number" name="positions[{{ $question->id }}]" value="{{ $question->position }}" min="0" max="99999" required></td>
                        <td><a class="link" href="{{ route('reports.answers', $question) }}">{{ $question->answers_count }} ↗</a></td>
                        <td><a class="link" href="{{ route('questions.edit', $question) }}">Изменить →</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <button class="button secondary block-order-button">Сохранить порядок вопросов</button>
        </form>
    </section>
@empty
    <div class="panel empty"><span class="metric-icon" aria-hidden="true">☷</span><h2>Вопросов пока нет</h2><a class="link" href="{{ route('questions.create') }}">Добавить первый вопрос →</a></div>
@endforelse
</div>
@endsection
