@php
    $series = $chart->series();
    $colors = \App\Models\DashboardChart::COLORS;
    $total = $series->sum('count');
    $max = max(1, $series->max('count'));
@endphp
<article class="panel section-panel">
    <header class="section-head">
        <div><h3>{{ $chart->name }}</h3><p>{{ $chart->question->label }} · {{ \App\Models\DashboardChart::TYPES[$chart->type] }}</p></div>
        <a class="link" href="{{ route('charts.edit', $chart) }}" aria-label="Изменить диаграмму {{ $chart->name }}">Изменить</a>
    </header>
    @if($series->isEmpty())
        <p class="row-note">{{ $chart->type === 'histogram' && $chart->question->type !== \App\Models\QuestionType::Number ? 'Для гистограммы выберите числовой вопрос.' : 'Ответов пока нет.' }}</p>
    @elseif($chart->type === 'pie')
        @php
            $start = 0;
            $segments = [];
            foreach ($series as $index => $item) {
                $end = $start + 100 * $item['count'] / $total;
                $segments[] = $colors[$index]." {$start}% {$end}%";
                $start = $end;
            }
        @endphp
        <div class="pie-chart" aria-hidden="true" style="--segments: {{ implode(', ', $segments) }}"></div>
        <ul class="chart-legend">
            @foreach($series as $item)
                <li><i aria-hidden="true" style="--swatch: {{ $colors[$loop->index] }}"></i><span>{{ $item['label'] }}</span><strong>{{ $item['count'] }}</strong></li>
            @endforeach
        </ul>
    @elseif($chart->type === 'histogram')
        <div class="histogram" aria-hidden="true">
            @foreach($series as $item)
                <div class="chart-track"><span style="--percent: {{ 100 * $item['count'] / $max }}%"></span></div>
            @endforeach
        </div>
        <ul class="chart-legend">
            @foreach($series as $item)
                <li><span>{{ $item['label'] }}</span><strong>{{ $item['count'] }}</strong></li>
            @endforeach
        </ul>
    @else
        @foreach($series as $item)
            <div class="bar-row">
                <div><span>{{ $item['label'] }}</span><strong>{{ $item['count'] }}</strong></div>
                <div class="bar-track" aria-hidden="true"><span style="--percent: {{ 100 * $item['count'] / $max }}%"></span></div>
            </div>
        @endforeach
    @endif
</article>
