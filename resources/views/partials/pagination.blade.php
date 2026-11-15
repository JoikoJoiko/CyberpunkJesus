@if($pages->hasPages())
    <nav class="pagination" aria-label="Страницы">
        @if($pages->previousPageUrl())<a class="button secondary" href="{{ $pages->previousPageUrl() }}">← Назад</a>@endif
        <span>{{ $pages->currentPage() }} из {{ $pages->lastPage() }}</span>
        @if($pages->nextPageUrl())<a class="button secondary" href="{{ $pages->nextPageUrl() }}">Дальше →</a>@endif
    </nav>
@endif
