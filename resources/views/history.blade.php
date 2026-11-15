@extends('layout')
@section('title', 'История')
@section('content')
<header class="page-head">
    <div><h1>История<span>.</span></h1><p>Кто и когда менял анкеты и вопросы.</p></div>
</header>

<section class="panel" aria-label="История изменений">
    @if($entries->isNotEmpty())
        <div class="table-scroll" tabindex="0" role="region" aria-label="Журнал изменений">
            <table class="history-table">
                <thead><tr><th>Автор</th><th>Когда</th><th>Действие</th><th>Что изменилось</th></tr></thead>
                <tbody>
                @foreach($entries as $entry)
                    <tr>
                        <td><strong class="row-title">{{ $entry->actor_name }}</strong><small class="row-note">{{ $entry->actor_email }}</small></td>
                        <td><time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('d.m.Y H:i') }}</time></td>
                        <td><span class="tag">{{ ['created' => 'Создание', 'updated' => 'Редактирование', 'deleted' => 'Удаление'][$entry->operation] }}</span></td>
                        <td>
                            <strong class="row-title">{{ $entry->subject_label }}</strong>
                            <small class="row-note">{{ $entry->subject_type === 'player' ? 'Анкета' : 'Вопрос' }} №{{ $entry->subject_id }}</small>
                            @if($entry->fields !== [])
                                <ul class="history-fields">
                                    @foreach($entry->fields as $field)<li>{{ $field }}</li>@endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @include('partials.pagination', ['pages' => $entries])
    @else
        <div class="empty"><span class="metric-icon" aria-hidden="true">◷</span><h2>Записей пока нет</h2><p>Здесь появятся новые изменения анкет и вопросов.</p></div>
    @endif
</section>
@endsection
