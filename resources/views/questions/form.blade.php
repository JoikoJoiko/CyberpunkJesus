@extends('layout')
@section('title', $question->exists ? 'Изменить вопрос' : 'Новый вопрос')
@section('content')
<div class="form-frame">
<a class="back-link" href="{{ route('questions.index') }}">← К списку вопросов</a>
<header class="page-head"><div><h1>{{ $question->exists ? 'Изменить вопрос' : 'Новый вопрос' }}<span>.</span></h1>
    <p>{{ $question->exists ? 'Изменения появятся во всех анкетах.' : 'После сохранения вопрос появится во всех анкетах.' }}</p></div>
</header>
<form method="post" action="{{ $question->exists ? route('questions.update', $question) : route('questions.store') }}" class="form-page stack" data-question-form>
    @csrf
    @if($question->exists) @method('PUT') @endif
    <section class="panel section-panel field-grid">
        <label class="wide">Текст вопроса<input type="text" name="label" maxlength="180" placeholder="Какой предмет вы взяли бы на Луну?" value="{{ old('label', $question->label) }}" required autofocus></label>
        <label>Тип ответа<select name="type" data-question-type>
        @foreach(\App\Models\QuestionType::cases() as $type)
            <option value="{{ $type->value }}" @selected(old('type', $question->type->value) === $type->value)>{{ $type->label() }}</option>
        @endforeach
        </select></label>
        <label>Порядок в анкете<input type="number" name="position" min="0" max="99999" value="{{ old('position', $question->position) }}" required><small>Вопросы с меньшим числом идут раньше.</small></label>
        <label>Символьный код<input type="text" name="code" maxlength="64" pattern="[A-Za-z_][A-Za-z0-9_]*" value="{{ old('code', $question->code) }}"><small>Если оставить пустым, код заполнится автоматически. Для даты рождения введите birthDate.</small></label>
        <label>Блок<input type="text" name="block" list="question-blocks" maxlength="100" value="{{ old('block', $question->block) }}"><small>Выберите существующий блок или введите название нового.</small></label>
        <datalist id="question-blocks">
            @foreach($blocks as $block)<option value="{{ $block }}"></option>@endforeach
        </datalist>
        <label class="wide" data-question-options>Варианты ответа<textarea name="option_lines" rows="5" maxlength="6000">{{ old('option_lines', implode("\n", $question->options ?? [])) }}</textarea><small>Для типа «Выбор из списка». Каждый вариант с новой строки.</small></label>
    </section>
    <div class="actions"><button class="button primary">Сохранить вопрос</button><a class="button secondary" href="{{ route('questions.index') }}">Отмена</a></div>
</form>

@if($question->exists)
    <section class="delete-section" aria-labelledby="remove-question">
        <h2 id="remove-question">Удаление вопроса</h2>
        <p>Из всех анкет будут удалены ответы и уточнения к этому вопросу.</p>
        <form method="post" action="{{ route('questions.destroy', $question) }}" data-confirm="Вопрос «{{ $question->label }}», ответы и уточнения будут удалены из всех анкет.">
            @csrf @method('DELETE')
            <button class="button danger">Удалить вопрос</button>
        </form>
    </section>
@endif
</div>
@endsection
