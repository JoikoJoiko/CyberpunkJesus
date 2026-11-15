<?php

namespace App\Http\Controllers;

use App\Models\ChangeLog;
use App\Models\Question;
use App\Models\QuestionBlock;
use App\Models\Questionnaire;
use App\Models\QuestionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(): View
    {
        return view('questions.index', [
            'groups' => QuestionBlock::groupQuestions(Question::ordered()->withCount(['answers' => fn ($query) => $query->answered()])->get()),
            'blocks' => QuestionBlock::ordered(),
        ]);
    }

    public function create(Request $request): View
    {
        $default = $request->validate(['block' => ['nullable', 'string', 'max:100']])['block'] ?? null;

        return view('questions.form', [
            'blocks' => $this->blocks(),
            'question' => new Question([
                'type' => QuestionType::Text,
                'block' => $default,
                'position' => min(99999, Question::where('block', $default)->max('position') + 10),
            ]),
        ]);
    }

    public function edit(Question $question): View
    {
        return view('questions.form', ['question' => $question, 'blocks' => $this->blocks()]);
    }

    private function blocks()
    {
        return QuestionBlock::ordered()->pluck('name')->filter(fn ($name) => $name !== '');
    }

    public function reorderBlocks(Request $request): RedirectResponse
    {
        $positions = $request->validate([
            'positions' => ['required', 'array'],
            'positions.*' => ['required', 'integer', 'between:0,99999'],
        ])['positions'];

        DB::transaction(function () use ($positions) {
            Questionnaire::revision(lock: true);
            foreach (QuestionBlock::ordered() as $block) {
                if (array_key_exists($block->id, $positions)) {
                    $block->update(['position' => $positions[$block->id]]);
                }
            }
        }, attempts: 3);

        return to_route('questions.index')->with('status', 'Порядок блоков обновлён.');
    }

    public function reorderQuestions(Request $request, QuestionBlock $block): RedirectResponse
    {
        $positions = $request->validate([
            'positions' => ['required', 'array'],
            'positions.*' => ['required', 'integer', 'between:0,99999'],
        ])['positions'];

        DB::transaction(function () use ($request, $block, $positions) {
            Questionnaire::revision(lock: true);
            $questions = Question::where('block', $block->name === '' ? null : $block->name)
                ->whereKey(array_keys($positions))->get();
            foreach ($questions->filter(fn (Question $question) => ($question->block ?? '') === $block->name) as $question) {
                $question->fill(['position' => $positions[$question->id]]);
                if ($question->isDirty('position')) {
                    $question->save();
                    ChangeLog::record($request->user(), $question, 'updated', ['Порядок в блоке']);
                }
            }
        }, attempts: 3);

        return to_route('questions.index')->with('status', 'Порядок вопросов в блоке обновлён.');
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        return $this->save($request, $question);
    }

    public function destroy(Request $request, Question $question): RedirectResponse
    {
        DB::transaction(function () use ($request, $question) {
            Questionnaire::revision(lock: true);
            $current = Question::findOrFail($question->id);
            $current->delete();
            Questionnaire::advance();
            ChangeLog::record($request->user(), $current, 'deleted');
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
            'code' => ['nullable', 'string', 'max:64', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/D'],
            'block' => ['nullable', 'string', 'max:100'],
        ], [
            'code.regex' => 'Код должен начинаться с латинской буквы или подчёркивания и содержать только латиницу, цифры и подчёркивания.',
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

        DB::transaction(function () use ($request, $existing, $data, $type, $options) {
            Questionnaire::revision(lock: true);
            $question = $existing ? Question::findOrFail($existing->id) : new Question;
            $code = $data['code'] ?? $question->code;
            $used = Question::query()->when($existing, fn ($query) => $query->whereKeyNot($question->id));
            $codeExists = fn (string $value) => (clone $used)->whereRaw('LOWER(code) = ?', [strtolower($value)])->exists();

            if ($code === null) {
                $base = Str::snake(Str::camel(Str::slug($data['label'])));
                $base = preg_match('/^[a-z_]/', $base) ? substr($base, 0, 50) : 'question';
                $code = $base;
                for ($suffix = 2; strcasecmp($code, Question::BIRTH_DATE_CODE) === 0 || $codeExists($code); $suffix++) {
                    $code = $base.'_'.$suffix;
                }
            } elseif ($codeExists($code)) {
                throw ValidationException::withMessages(['code' => 'Этот код уже используется.']);
            }

            if (strcasecmp($code, Question::BIRTH_DATE_CODE) === 0) {
                if ($type !== QuestionType::Date) {
                    throw ValidationException::withMessages(['type' => 'Для кода birthDate выберите тип «Дата».']);
                }
                $code = Question::BIRTH_DATE_CODE;
            }

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

            $question->fill($data)->fill(['type' => $type, 'options' => $options, 'code' => $code]);
            $fields = array_values(array_intersect_key([
                'label' => 'Текст вопроса',
                'type' => 'Тип ответа',
                'options' => 'Варианты ответа',
                'position' => 'Порядок в анкете',
                'code' => 'Символьный код',
                'block' => 'Блок',
            ], $question->getDirty()));
            if ($existing && $fields === []) {
                return;
            }

            $question->save();
            $blockName = $question->block ?? '';
            if (! QuestionBlock::ordered()->contains(fn (QuestionBlock $block) => $block->name === $blockName)) {
                QuestionBlock::create(['name' => $blockName, 'position' => min(99999, QuestionBlock::max('position') + 10)]);
            }
            Questionnaire::advance();
            ChangeLog::record($request->user(), $question, $existing ? 'updated' : 'created', $fields);
        }, attempts: 3);

        return to_route('questions.index')->with('status', $existing ? 'Вопрос обновлён.' : 'Вопрос добавлен.');
    }
}
