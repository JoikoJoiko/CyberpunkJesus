<?php

namespace App\Http\Controllers;

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
            'server' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'max:40'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);
        $questions = Question::ordered()->get();
        $query = Player::query()->select('players.*')->withCount('answers');
        $search = $filters['q'] ?? '';
        $server = $filters['server'] ?? '';
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $sortQuestion = $questions->first(fn (Question $question) => 'question_'.$question->id === $sort);

        if ($search !== '') {
            $like = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $query->where(function ($query) use ($like) {
                $query->whereRaw("nickname LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("server LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("notes LIKE ? ESCAPE '!'", [$like]);
            });
        }

        if ($server !== '') {
            $query->where('players.server', $server);
        }

        $columns = [
            'created_at' => 'players.created_at',
            'nickname' => 'players.nickname',
            'server' => 'players.server',
            'interviewed_on' => 'players.interviewed_on',
        ];
        $column = $columns[$sort] ?? $columns['created_at'];

        if ($sortQuestion) {
            $query->leftJoin('answers as sorting_answer', function ($join) use ($sortQuestion) {
                $join->on('sorting_answer.player_id', '=', 'players.id')
                    ->where('sorting_answer.question_id', $sortQuestion->id);
            })->addSelect('sorting_answer.value as sorted_value');

            $column = $sortQuestion->type === QuestionType::Number
                ? 'sorting_answer.numeric_value'
                : 'sorting_answer.value';
        } else {
            $sort = isset($columns[$sort]) ? $sort : 'created_at';
        }

        $players = $query->orderByRaw("CASE WHEN {$column} IS NULL THEN 1 ELSE 0 END")
            ->orderBy($column, $direction)
            ->orderByDesc('players.id')
            ->paginate(15)
            ->withQueryString();

        $servers = Player::query()->whereNotNull('server')->distinct()->orderBy('server')->pluck('server');

        return view('players.index', compact(
            'players', 'questions', 'servers', 'search', 'server', 'sort', 'direction', 'sortQuestion'
        ));
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

    public function destroy(Player $player): RedirectResponse
    {
        $player->delete();

        return to_route('players.index')->with('status', 'Анкета удалена.');
    }

    private function form(Player $player): View
    {
        $revision = Questionnaire::revision();
        $questions = Question::ordered()->get();
        $answers = $player->exists ? $player->answers()->pluck('value', 'question_id') : collect();

        return view('players.form', compact('player', 'revision', 'questions', 'answers'));
    }

    private function save(Request $request, ?Player $existing = null): RedirectResponse
    {
        $meta = $request->validate([
            'nickname' => ['nullable', 'string', 'max:100'],
            'server' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'interviewed_on' => ['nullable', ...QuestionType::Date->rules()],
            'questionnaire_revision' => ['required', 'integer'],
            'player_revision' => [$existing ? 'required' : 'nullable', 'integer'],
            'answers' => ['nullable', 'array'],
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
            foreach ($questions as $question) {
                $key = "answers.{$question->id}";
                $rules[$key] = ['nullable', ...$question->type->rules($question->options ?? [])];
                $labels[$key] = $question->label;
                if ($question->type === QuestionType::Number && is_string($input[$question->id] ?? null)) {
                    $input[$question->id] = str_replace(',', '.', $input[$question->id]);
                }
            }
            $values = validator(['answers' => $input], $rules, [], $labels)->validate()['answers'] ?? [];

            $player->fill($request->only(['nickname', 'server', 'notes', 'interviewed_on']));
            if ($existing) {
                $player->revision++;
            }
            $player->save();

            $rows = [];
            foreach ($questions as $question) {
                $value = $values[$question->id] ?? null;
                if ($value !== null) {
                    $rows[] = [
                        'player_id' => $player->id,
                        'question_id' => $question->id,
                        ...$question->type->answerAttributes($value),
                    ];
                }
            }

            $player->answers()->whereNotIn('question_id', array_column($rows, 'question_id'))->delete();
            if ($rows !== []) {
                Answer::query()->upsert($rows, ['player_id', 'question_id'], ['value', 'numeric_value']);
            }
        }, attempts: 3);

        return to_route('players.index')->with('status', $existing ? 'Анкета обновлена.' : 'Анкета добавлена.');
    }
}
