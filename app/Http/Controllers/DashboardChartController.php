<?php

namespace App\Http\Controllers;

use App\Models\DashboardChart;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardChartController extends Controller
{
    public function create(): View
    {
        return $this->form(new DashboardChart(['type' => 'bar']));
    }

    public function edit(DashboardChart $chart): View
    {
        return $this->form($chart);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, new DashboardChart);
    }

    public function update(Request $request, DashboardChart $chart): RedirectResponse
    {
        return $this->save($request, $chart);
    }

    public function destroy(DashboardChart $chart): RedirectResponse
    {
        $chart->delete();

        return to_route('dashboard')->with('status', 'Диаграмма удалена.');
    }

    private function form(DashboardChart $chart): View
    {
        return view('charts.form', ['chart' => $chart, 'questions' => Question::ordered()->get()]);
    }

    private function save(Request $request, DashboardChart $chart): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'question_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(array_keys(DashboardChart::TYPES))],
        ]);

        DB::transaction(function () use ($data, $chart) {
            Questionnaire::revision(lock: true);
            $question = Question::find($data['question_id']);
            if ($question === null) {
                throw ValidationException::withMessages(['question_id' => 'Выберите существующий вопрос.']);
            }
            if ($data['type'] === 'histogram' && $question->type !== QuestionType::Number) {
                throw ValidationException::withMessages(['type' => 'Для гистограммы нужен числовой вопрос.']);
            }
            $chart->fill($data)->save();
        }, attempts: 3);

        return to_route('dashboard')->with('status', 'Диаграмма сохранена.');
    }
}
