<?php

namespace App\Http\Controllers;

use App\Models\ChangeLog;
use App\Models\Answer;
use App\Models\Player;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $query = Player::query()->withCount(['answers' => fn ($query) => $query->answered()])
            ->addSelect(['birth_date' => Answer::query()->select('answers.value')
                ->join('questions', 'questions.id', '=', 'answers.question_id')
                ->whereColumn('answers.player_id', 'players.id')
                ->where('questions.code', Question::BIRTH_DATE_CODE)
                ->limit(1)]);
        $search = $filters['q'] ?? '';

        if ($search !== '') {
            $like = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $query->where(function ($query) use ($like) {
                $query->whereRaw("nickname LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("notes LIKE ? ESCAPE '!'", [$like]);
            });
        }

        $players = $query->latest('id')->paginate(15)->appends($filters);

        return view('players.index', compact('players', 'search'));
    }

    public function create(): View
    {
        return $this->form(new Player);
    }

    public function edit(Player $player): View
    {
        return $this->form($player);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request);
    }

    public function update(Request $request, Player $player): RedirectResponse
    {
        return $this->save($request, $player);
    }

    public function destroy(Request $request, Player $player): RedirectResponse
    {
        DB::transaction(function () use ($request, $player) {
            $current = Player::query()->lockForUpdate()->findOrFail($player->id);
            $current->delete();
            ChangeLog::record($request->user(), $current, 'deleted');
        }, attempts: 3);

        return to_route('players.index')->with('status', 'Анкета удалена.');
    }

    private function form(Player $player): View
    {
        $revision = Questionnaire::revision();
        $questions = Question::ordered()->get();
        $answers = $player->exists ? $player->answers()->get()->keyBy('question_id') : collect();
        $birthQuestion = $questions->firstWhere('code', Question::BIRTH_DATE_CODE);
        $age = Player::ageFrom($answers->get($birthQuestion?->id)?->value);
        $blocks = $questions->groupBy(fn (Question $question) => $question->block ?? '');

        return view('players.form', compact('player', 'revision', 'blocks', 'answers', 'age'));
    }

    private function save(Request $request, ?Player $existing = null): RedirectResponse
    {
        $meta = $request->validate([
            'nickname' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'interviewed_on' => ['nullable', ...QuestionType::Date->rules()],
            'questionnaire_revision' => ['required', 'integer'],
            'player_revision' => [$existing ? 'required' : 'nullable', 'integer'],
            'answers' => ['nullable', 'array'],
            'clarifications' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($request, $existing, $meta) {
            if (Questionnaire::revision(lock: true) !== (int) $meta['questionnaire_revision']) {
                throw ValidationException::withMessages([
                    'questionnaire_revision' => 'Список вопросов изменился. Проверьте ответы в обновлённой форме и сохраните её ещё раз.',
                ]);
            }

            $player = $existing ? Player::query()->lockForUpdate()->findOrFail($existing->id) : new Player;
            if ($existing && $player->revision !== (int) $meta['player_revision']) {
                throw ValidationException::withMessages([
                    'player_revision' => 'Эту анкету уже изменили. Откройте свежую версию, чтобы сверить изменения.',
                ]);
            }

            $questions = Question::ordered()->get();
            $rules = [];
            $labels = [];
            $input = $meta['answers'] ?? [];
            $clarifications = $meta['clarifications'] ?? [];
            foreach ($questions as $question) {
                $key = "answers.{$question->id}";
                $rules[$key] = ['nullable', ...$question->type->rules($question->options ?? [])];
                $labels[$key] = $question->label;
                $rules["clarifications.{$question->id}"] = ['nullable', 'string', 'max:5000'];
                if ($question->type === QuestionType::Number && is_string($input[$question->id] ?? null)) {
                    $input[$question->id] = str_replace(',', '.', $input[$question->id]);
                }
            }
            $validated = validator(['answers' => $input, 'clarifications' => $clarifications], $rules, [], $labels)->validate();

            $player->fill(array_intersect_key($meta, array_flip($player->getFillable())));
            $fields = array_values(array_intersect_key([
                'nickname' => 'Ник',
                'notes' => 'Примечание об игроке',
                'interviewed_on' => 'Дата опроса',
            ], $player->getDirty()));

            $rows = [];
            $previousAnswers = $existing ? $player->answers()->get()->keyBy('question_id') : collect();
            foreach ($questions as $question) {
                $previous = $previousAnswers->get($question->id);
                $value = $validated['answers'][$question->id] ?? null;
                $clarification = array_key_exists($question->id, $clarifications)
                    ? $validated['clarifications'][$question->id] ?? null
                    : $previous?->clarification;
                $attributes = $value === null
                    ? ['value' => null, 'numeric_value' => null]
                    : $question->type->answerAttributes($value);

                if ($previous?->value !== $attributes['value']) {
                    $fields[] = "Ответ «{$question->label}»";
                }
                if ($previous?->clarification !== $clarification) {
                    $fields[] = "Уточнение «{$question->label}»";
                }
                if ($value !== null || $clarification !== null) {
                    $rows[$question->id] = [
                        'question_id' => $question->id,
                        'clarification' => $clarification,
                        ...$attributes,
                    ];
                }
            }

            if ($existing && $fields === []) {
                return;
            }
            if ($existing) {
                $player->revision++;
            }
            $player->save();

            $player->answers()->whereNotIn('question_id', array_keys($rows))->delete();
            if ($rows !== []) {
                $player->answers()->upsert(array_values($rows), ['player_id', 'question_id'], ['value', 'numeric_value', 'clarification']);
            }
            ChangeLog::record($request->user(), $player, $existing ? 'updated' : 'created', $fields);
        }, attempts: 3);

        return to_route('players.index')->with('status', $existing ? 'Анкета обновлена.' : 'Анкета добавлена.');
    }
}
