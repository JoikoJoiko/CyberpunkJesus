@extends('layout')
@section('title', $chart->exists ? 'Изменить диаграмму' : 'Новая диаграмма')
@section('content')
<div class="form-frame">
    <a class="back-link" href="{{ route('dashboard') }}">← К обзору</a>
    <header class="page-head"><h1>{{ $chart->exists ? 'Изменить диаграмму' : 'Новая диаграмма' }}<span>.</span></h1></header>
    @if($questions->isEmpty())
        <div class="panel empty"><p>Для диаграммы нужен хотя бы один вопрос.</p><a class="link" href="{{ route('questions.create') }}">Добавить вопрос →</a></div>
    @else
        <form class="panel section-panel stack" method="post" action="{{ $chart->exists ? route('charts.update', $chart) : route('charts.store') }}">
            @csrf
            @if($chart->exists) @method('PUT') @endif
            <label>Название<input name="name" maxlength="100" value="{{ old('name', $chart->name) }}" required></label>
            <label>Вопрос
                <select name="question_id" required>
                    <option value="">Выберите вопрос</option>
                    @foreach($questions as $question)
                        <option value="{{ $question->id }}" @selected((string) old('question_id', $chart->question_id) === (string) $question->id)>
                            {{ $question->label }} · {{ $question->code }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>Вид диаграммы
                <select name="type">
                    @foreach(\App\Models\DashboardChart::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $chart->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <small>Гистограмма группирует числовые ответы по диапазонам.</small>
            </label>
            <div><button class="button primary">Сохранить диаграмму</button></div>
        </form>
    @endif
    @if($chart->exists)
        <form class="delete-section" method="post" action="{{ route('charts.destroy', $chart) }}" data-confirm="Диаграмма будет удалена с главной страницы.">
            @csrf @method('DELETE')
            <button class="button danger">Удалить диаграмму</button>
        </form>
    @endif
</div>
@endsection
