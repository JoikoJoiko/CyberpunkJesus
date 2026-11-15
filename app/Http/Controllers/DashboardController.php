<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Player;
use App\Models\Question;
use App\Models\QuestionType;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $days = collect(range(13, 0))->map(fn ($offset) => today()->subDays($offset));
        $activity = Player::query()->where('created_at', '>=', $days->first())
            ->groupByRaw('DATE(created_at)')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->pluck('total', 'day');

        return view('dashboard', [
            'playerCount' => Player::count(),
            'answerCount' => Answer::count(),
            'questionCount' => Question::count(),
            'recent' => Player::latest('id')->limit(5)->withCount('answers')->get(),
            'activity' => $days->map(fn ($day) => [
                'label' => $day->format('d.m'),
                'count' => (int) ($activity[$day->toDateString()] ?? 0),
            ]),
        ]);
    }

    public function answers(?Question $question = null): View
    {
        $questions = Question::ordered()->withCount('answers')->get();
        $playerCount = Player::count();
        $selected = $question ? $questions->firstWhere('id', $question->id) : $questions->first();
        $distribution = collect();
        $summary = null;

        if ($selected && $selected->answers_count > 0) {
            $answers = $selected->answers();
            $distribution = (clone $answers)->toBase()
                ->selectRaw('value, COUNT(*) as total')
                ->groupBy('value')->orderByDesc('total')->orderBy('value')->limit(8)->get();

            $summary = match ($selected->type) {
                QuestionType::Number => (clone $answers)->toBase()
                    ->selectRaw('MIN(numeric_value) as minimum, AVG(numeric_value) as average, MAX(numeric_value) as maximum')->first(),
                QuestionType::Date => (clone $answers)->toBase()
                    ->selectRaw('MIN(value) as minimum, MAX(value) as maximum')->first(),
                default => null,
            };
        }

        return view('reports', compact('questions', 'playerCount', 'selected', 'distribution', 'summary'));
    }
}
