@extends('layout')
@section('title', $player->exists ? 'Анкета игрока' : 'Новая анкета')
@section('content')
<div class="form-frame">
<a class="back-link" href="{{ route('players.index') }}">← К списку игроков</a>
<header class="page-head">
    <div><h1>{{ $player->exists ? ($player->nickname ?? 'Анкета без ника') : 'Новая анкета' }}<span>.</span></h1><p>Заполните любые поля и сохраните анкету.</p></div>
    @if($age !== null)<span class="tag">Возраст · {{ $age }}</span>@endif
</header>

<form method="post" action="{{ $player->exists ? route('players.update', $player) : route('players.store') }}" class="stack form-page">
    @csrf
    @if($player->exists) @method('PUT') @endif
    <input type="hidden" name="questionnaire_revision" value="{{ $revision }}">
    @if($player->exists)<input type="hidden" name="player_revision" value="{{ old('player_revision', $player->revision) }}">@endif

    <section class="panel section-panel">
        <header class="section-head"><h2>Об игроке</h2></header>
        <div class="field-grid">
            <label>Ник<input type="text" name="nickname" maxlength="100" value="{{ old('nickname', $player->nickname) }}" placeholder="Например, PixelGhost"></label>
            <label>Дата опроса<input type="date" name="interviewed_on" min="1000-01-01" max="9999-12-31" value="{{ old('interviewed_on', $player->interviewed_on?->toDateString()) }}"></label>
            <label class="wide">Примечание<textarea name="notes" rows="3" maxlength="5000">{{ old('notes', $player->notes) }}</textarea></label>
        </div>
    </section>

    <section class="panel section-panel">
        <header class="section-head"><h2>Вопросы опроса</h2><a class="link" href="{{ route('questions.index') }}">К списку вопросов ↗</a></header>
        @forelse($blocks as $block => $questions)
        <fieldset class="question-block field-grid">
        <legend>{{ $block !== '' ? $block : 'Без блока' }}</legend>
        @foreach($questions as $question)
            @php
                $field = 'answers['.$question->id.']';
                $answer = $answers->get($question->id);
                $oldValue = old('answers.'.$question->id, $answer?->value);
                $value = is_scalar($oldValue) ? (string) $oldValue : '';
                $clarificationKey = 'clarifications.'.$question->id;
                $oldClarification = old($clarificationKey, $answer?->clarification);
                $clarification = is_string($oldClarification) ? $oldClarification : '';
            @endphp
            <div @class(['question-field', 'wide' => $question->type === \App\Models\QuestionType::Paragraph])>
            <label>
            {{ $question->label }}
            @switch($question->type)
                @case(\App\Models\QuestionType::Paragraph)
                    <textarea name="{{ $field }}" rows="3" maxlength="5000">{{ $value }}</textarea>
                    @break
                @case(\App\Models\QuestionType::Number)
                    <input type="text" name="{{ $field }}" inputmode="decimal" maxlength="17" value="{{ $value }}" placeholder="Например, 12,5">
                    @break
                @case(\App\Models\QuestionType::Date)
                    <input type="date" name="{{ $field }}" value="{{ $value }}" min="1000-01-01" max="9999-12-31">
                    @break
                @case(\App\Models\QuestionType::Boolean)
                    <select name="{{ $field }}"><option value="">Без ответа</option><option value="1" @selected($value === '1')>Да</option><option value="0" @selected($value === '0')>Нет</option></select>
                    @break
                @case(\App\Models\QuestionType::Choice)
                    <select name="{{ $field }}"><option value="">Без ответа</option>@foreach($question->options as $option)<option value="{{ $option }}" @selected($option === $value)>{{ $option }}</option>@endforeach</select>
                    @break
                @default
                    <input type="text" name="{{ $field }}" value="{{ $value }}" maxlength="255">
            @endswitch
            @error('answers.'.$question->id)<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <details class="clarification" @if($errors->has($clarificationKey)) open @endif>
                <summary @class(['clarification-toggle', 'has-clarification' => $clarification !== ''])
                    aria-label="Уточнение к вопросу «{{ $question->label }}»{{ $clarification !== '' ? ' (есть текст)' : '' }}"
                    title="Уточнение к ответу">
                    <span class="clarification-closed" aria-hidden="true">+</span>
                    <span class="clarification-open" aria-hidden="true">−</span>
                </summary>
                <label>Уточнение к ответу
                    <textarea name="clarifications[{{ $question->id }}]" rows="2" maxlength="5000">{{ $clarification }}</textarea>
                    @error($clarificationKey)<small class="field-error">{{ $message }}</small>@enderror
                </label>
            </details>
            </div>
        @endforeach
        </fieldset>
        @empty
            <div class="wide empty compact"><p>Список вопросов пуст.</p><a class="link" href="{{ route('questions.create') }}">Добавить вопрос →</a></div>
        @endforelse
    </section>

    <div class="actions">
        @if($errors->has('player_revision'))
            <a class="button primary" href="{{ route('players.edit', $player) }}">Открыть свежую версию</a>
        @else
            <button class="button primary">Сохранить анкету</button>
        @endif
        <a class="button secondary" href="{{ $player->exists ? route('players.show', $player) : route('players.index') }}">Отмена</a>
    </div>
</form>
@if($player->exists)
    <form class="delete-section" method="post" action="{{ route('players.destroy', $player) }}" data-confirm="Анкета, ответы и уточнения этого игрока будут удалены из архива.">
        @csrf @method('DELETE')
        <button class="button danger">Удалить анкету</button>
    </form>
@endif
</div>
@endsection
