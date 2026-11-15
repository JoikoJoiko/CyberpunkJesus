@extends('layout')
@section('title', 'Вопросы')
@section('content')
<header class="page-head">
    <div><h1>Вопросы<span>.</span></h1><p>Этот список задаёт поля во всех анкетах.</p></div>
    <a class="button primary" href="{{ route('questions.create') }}">+ Новый вопрос</a>
</header>
<section class="panel" aria-label="Вопросы анкеты">
    @if($questions->isNotEmpty())
        <div class="table-scroll" tabindex="0" role="region" aria-label="Таблица вопросов">
            <table>
                <thead><tr><th>Вопрос</th><th>Тип ответа</th><th>Порядок</th><th>Ответы</th><th><span class="sr-only">Действия</span></th></tr></thead>
                <tbody>
                @foreach($questions as $question)
                    <tr>
                        <td><a class="row-title" href="{{ route('questions.edit', $question) }}">{{ $question->label }}</a></td>
                        <td><span class="tag">{{ $question->type->label() }}</span></td>
                        <td>{{ $question->position }}</td>
                        <td><a class="link" href="{{ route('reports.answers', $question) }}">{{ $question->answers_count }} ↗</a></td>
                        <td><a class="link" href="{{ route('questions.edit', $question) }}">Изменить →</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty"><span class="metric-icon" aria-hidden="true">☷</span><h2>Вопросов пока нет</h2><a class="link" href="{{ route('questions.create') }}">Добавить первый вопрос →</a></div>
    @endif
</section>
@endsection
