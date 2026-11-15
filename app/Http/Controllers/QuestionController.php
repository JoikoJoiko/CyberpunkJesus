<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(): View
    {
        return view('questions.index', [
            'questions' => Question::ordered()->withCount(['answers' => fn ($query) => $query->answered()])->get(),
        ]);
    }

    public function create(): View
    {
        return view('questions.form', [
            'question' => new Question([
                'type' => QuestionType::Text,
                'position' => min(99999, Question::max('position') + 10),
            ]),
        ]);
    }

    public function edit(Question $question): View
    {
        return view('questions.form', compact('question'));
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        return $this->save($request, $question);
    }

    public function destroy(Question $question): RedirectResponse
    {
        DB::transaction(function () use ($question) {
            Questionnaire::revision(lock: true);
            $question->delete();
            Questionnaire::advance();
        }, attempts: 3);

        return to_route('questions.index')->with('status', 'Вопрос, ответы и уточнения удалены.');
    }

    private function save(Request $request, ?Question $existing = null): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:180'],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'position' => ['required', 'integer', 'between:0,99999'],
            'option_lines' => ['nullable', 'string', 'max:6000'],
        ]);
        $type = QuestionType::from($data['type']);
        $options = null;

        if ($type === QuestionType::Choice) {
            $lines = preg_split('/\R/u', $data['option_lines'] ?? '');
            $options = array_values(array_unique(array_filter(array_map('trim', $lines), 'strlen')));
            validator(['options' => $options], [
                'options' => ['required', 'array', 'min:2', 'max:50'],
                'options.*' => ['string', 'max:100'],
            ], [
                'options.min' => 'Добавьте хотя бы два разных варианта.',
                'options.required' => 'Добавьте хотя бы два разных варианта.',
                'options.max' => 'Максимум 50 вариантов.',
                'options.*.max' => 'Каждый вариант должен быть короче 101 символа.',
            ])->validate();
        }

        DB::transaction(function () use ($existing, $data, $type, $options) {
            Questionnaire::revision(lock: true);
            $question = $existing ? Question::findOrFail($existing->id) : new Question;

            if ($existing && ($type !== $question->type || $options !== $question->options)) {
                foreach ($question->answers()->answered()->lazyById() as $answer) {
                    $value = $question->type === QuestionType::Boolean && $type !== QuestionType::Number
                        ? $question->displayAnswer($answer->value)
                        : $answer->value;
                    if ($type === QuestionType::Boolean) {
                        $value = match (mb_strtolower($value)) {
                            'да' => '1',
                            'нет' => '0',
                            default => $value,
                        };
                    }
                    if ($type === QuestionType::Number) {
                        $value = str_replace(',', '.', $value);
                    }

                    if (validator(['value' => $value], ['value' => $type->rules($options ?? [])])->fails()) {
                        throw ValidationException::withMessages([
                            'type' => 'Есть сохранённые ответы, которые не подходят под новый тип или варианты. Сначала исправьте их в анкетах.',
                        ]);
                    }
                    $answer->update($type->answerAttributes($value));
                }
            }

            $question->fill($data)->fill(['type' => $type, 'options' => $options])->save();
            Questionnaire::advance();
        }, attempts: 3);

        return to_route('questions.index')->with('status', $existing ? 'Вопрос обновлён.' : 'Вопрос добавлен.');
    }
}
