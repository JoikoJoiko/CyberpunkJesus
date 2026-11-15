<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class DashboardChart extends Model
{
    public const TYPES = ['bar' => 'Столбчатая диаграмма', 'pie' => 'Круговая диаграмма', 'histogram' => 'Гистограмма'];

    public const COLORS = ['#fa68b7', '#ce4591', '#ff9ccd', '#a73577', '#e984bb', '#873367', '#ffbadc', '#ae6993'];

    public $timestamps = false;

    protected $fillable = ['question_id', 'name', 'type'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function series(): Collection
    {
        $answers = $this->question->answers()->answered()->toBase();
        if ($this->type === 'histogram') {
            return $this->question->type === QuestionType::Number ? $this->histogram($answers) : collect();
        }

        $series = (clone $answers)->selectRaw('value, COUNT(*) as total')->groupBy('value')
            ->orderByDesc('total')->orderBy('value')->limit(7)->get()
            ->map(fn ($row) => ['label' => $this->question->displayAnswer($row->value), 'count' => (int) $row->total]);
        $remaining = $answers->count() - $series->sum('count');
        if ($remaining > 0) {
            $series->push(['label' => 'Остальные ответы', 'count' => $remaining]);
        }

        return $series;
    }

    private function histogram(Builder $answers): Collection
    {
        $range = (clone $answers)->selectRaw('MIN(numeric_value) AS minimum, MAX(numeric_value) AS maximum')->first();
        if ($range->minimum === null) {
            return collect();
        }

        $min = (float) $range->minimum;
        $max = (float) $range->maximum;
        if ($min === $max) {
            return collect([['label' => (string) $min, 'count' => $answers->count()]]);
        }

        $width = ($max - $min) / 6;
        $counts = array_fill(0, 6, 0);
        foreach ($answers->whereBetween('numeric_value', [$min, $max])->select('numeric_value')->cursor() as $answer) {
            $index = (int) floor(((float) $answer->numeric_value - $min) / $width);
            $counts[min(5, max(0, $index))]++;
        }

        return collect($counts)->map(fn ($count, $index) => [
            'label' => round($min + $index * $width, 8).' — '.round($min + ($index + 1) * $width, 8),
            'count' => $count,
        ]);
    }
}
