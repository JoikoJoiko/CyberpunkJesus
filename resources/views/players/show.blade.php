@extends('layout')
@section('title', $title)
@section('content')
<div class="form-frame">
    <div class="print-actions">
        <a class="back-link" href="{{ route('players.index') }}">← К списку игроков</a>
        <div class="actions">
            <a class="button primary" href="{{ route('players.edit', $player) }}">Редактировать</a>
            <a class="button secondary" href="{{ route('players.pdf', $player) }}">Скачать PDF</a>
            <a class="button secondary" href="{{ route('players.docx', $player) }}">Скачать DOCX</a>
        </div>
    </div>
    <article class="print-sheet">
        @include('players.print-content')
    </article>
</div>
@endsection
