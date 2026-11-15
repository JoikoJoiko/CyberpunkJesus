<?php

namespace App\Http\Controllers;

use App\Models\ChangeLog;
use App\Models\Answer;
use App\Models\Player;
use App\Models\Question;
use App\Models\QuestionBlock;
use App\Models\Questionnaire;
use App\Models\QuestionType;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function show(Player $player): View
    {
        return view('players.show', $this->printData($player));
    }

    public function pdf(Player $player): Response
    {
        $pdf = new Dompdf(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);
        $pdf->setPaper('A4', 'portrait');
        $pdf->loadHtml(view('players.pdf', $this->printData($player))->render(), 'UTF-8');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="player-'.$player->id.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function docx(Player $player): StreamedResponse
    {
        $data = $this->printData($player);
        Settings::setOutputEscapingEnabled(true);
        $document = new PhpWord;
        $document->setDefaultFontName('Arial');
        $document->setDefaultFontSize(11);
        $document->addTitleStyle(1, ['size' => 20, 'bold' => true, 'color' => '851650']);
        $document->addTitleStyle(2, ['size' => 14, 'bold' => true, 'color' => '851650']);
        $section = $document->addSection(['paperSize' => 'A4', 'orientation' => 'portrait']);
        $section->addTitle($data['title'], 1);
        foreach ($data['sections'] as $group) {
            $section->addTitle($group['heading'], 2);
            foreach ($group['rows'] as $row) {
                $section->addText($row['label'], ['bold' => true], ['keepNext' => true]);
                foreach (preg_split('/\R/u', $row['value'] ?? '—') as $line) {
                    $section->addText($line);
                }
                if ($row['clarification'] !== null) {
                    foreach (preg_split('/\R/u', 'Уточнение: '.$row['clarification']) as $line) {
                        $section->addText($line, ['italic' => true, 'color' => '59505A']);
                    }
                }
            }
        }

        return response()->streamDownload(
            fn () => IOFactory::createWriter($document, 'Word2007')->save('php://output'),
            'player-'.$player->id.'.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Cache-Control' => 'private, no-store'],
        );
    }

    private function printData(Player $player): array
    {
        $questions = Question::ordered()->get();
        $answers = $player->answers()->get()->keyBy('question_id');
        $birthQuestion = $questions->firstWhere('code', Question::BIRTH_DATE_CODE);
        $age = Player::ageFrom($answers->get($birthQuestion?->id)?->value);
        $rows = [
            ['label' => 'Ник', 'value' => $player->nickname, 'clarification' => null],
            ['label' => 'Возраст', 'value' => $age === null ? null : (string) $age, 'clarification' => null],
            ['label' => 'Дата опроса', 'value' => $player->interviewed_on?->format('d.m.Y'), 'clarification' => null],
            ['label' => 'Примечание', 'value' => $player->notes, 'clarification' => null],
        ];
        $sections = [['heading' => 'Об игроке', 'rows' => $rows]];
        foreach (QuestionBlock::groupQuestions($questions) as $name => $group) {
            $sections[] = [
                'heading' => $name === '' ? 'Без блока' : (string) $name,
                'rows' => $group->map(function (Question $question) use ($answers) {
                    $answer = $answers->get($question->id);

                    return [
                        'label' => $question->label,
                        'value' => $answer?->value !== null ? $question->displayAnswer($answer->value) : null,
                        'clarification' => $answer?->clarification,
                    ];
                }),
            ];
        }

        return ['player' => $player, 'title' => $player->nickname ?? 'Анкета без ника', 'sections' => $sections];
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
        $blocks = QuestionBlock::groupQuestions($questions);

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
