<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Player;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DossierTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_archive_pages_require_login_and_a_colleague_can_sign_in(): void
    {
        foreach (['dashboard', 'players.index', 'questions.index', 'reports.answers'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $this->get(route('login'))->assertOk()->assertSee('Вход в архив');

        $owner = User::factory()->create();
        User::factory()->create([
            'name' => 'Участница',
            'email' => 'friend@example.test',
            'password' => 'unique-test-password',
        ]);
        $this->actingAs($owner)->get('/accounts')->assertNotFound();
        $this->post('/accounts')->assertNotFound();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'email' => 'FRIEND@example.test',
            'password' => 'unique-test-password',
        ])->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk()->assertSee('Грехометр');
    }

    public function test_dynamic_answers_are_optional_and_cascade_on_delete(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('questions.create'))->assertOk()->assertSee('Короткий текст');
        $this->post(route('questions.store'), [
            'label' => 'Сколько голубей?',
            'type' => 'number',
            'position' => 10,
        ])->assertRedirect(route('questions.index'));
        $question = Question::sole();
        $revision = Questionnaire::revision();

        $this->get(route('questions.edit', $question))->assertOk()->assertSee('Сколько голубей?');
        $this->get(route('players.create'))->assertOk()->assertSee($question->label);
        $this->post(route('players.store'), ['questionnaire_revision' => $revision])
            ->assertRedirect(route('players.index'));
        $this->assertDatabaseHas('players', ['nickname' => null, 'notes' => null, 'interviewed_on' => null]);

        foreach (['Десять' => '10', 'Два' => '2,50'] as $nickname => $answer) {
            $this->post(route('players.store'), [
                'nickname' => $nickname,
                'interviewed_on' => '2026-11-15',
                'questionnaire_revision' => $revision,
                'answers' => [$question->id => $answer],
            ])->assertRedirect(route('players.index'));
        }

        $this->assertDatabaseHas('answers', ['value' => '2.5', 'numeric_value' => 2.5]);
        $this->get(route('players.index', ['q' => 'Два']))
            ->assertOk()->assertSee('Два')->assertDontSee('Десять');
        $this->get(route('reports.answers'))->assertOk()->assertSee('6.25');
        $this->get(route('dashboard'))->assertOk()->assertSee('Два');

        $player = Player::where('nickname', 'Два')->firstOrFail();
        $this->get(route('players.edit', $player))->assertOk()->assertSee('2.5');
        $this->put(route('players.update', $player), [
            'nickname' => 'Два',
            'questionnaire_revision' => $revision,
            'player_revision' => $player->revision,
            'answers' => [$question->id => ''],
        ])->assertRedirect(route('players.index'));
        $this->assertDatabaseMissing('answers', ['player_id' => $player->id]);
        $this->assertDatabaseCount('answers', 1);

        $this->delete(route('questions.destroy', $question))->assertRedirect(route('questions.index'));
        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('answers', 0);
        $this->assertDatabaseCount('players', 3);
        $this->get(route('players.create'))->assertOk()->assertDontSee('Сколько голубей?');
    }

    public function test_question_changes_validate_existing_answers_and_reject_stale_forms(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('questions.store'), [
            'label' => 'Кот?',
            'type' => 'choice',
            'position' => 10,
            'option_lines' => "Да\nНет",
        ])->assertRedirect(route('questions.index'));
        $question = Question::sole();
        $revision = Questionnaire::revision();

        $this->post(route('players.store'), [
            'questionnaire_revision' => $revision,
            'answers' => [$question->id => 'Нет'],
        ])->assertRedirect(route('players.index'));
        $this->put(route('questions.update', $question), [
            'label' => 'Кот?',
            'type' => 'choice',
            'position' => 10,
            'option_lines' => "Да\nМожет быть",
        ])->assertSessionHasErrors('type');
        $this->assertSame('Нет', Answer::sole()->value);

        $this->put(route('questions.update', $question), [
            'label' => 'Кот?',
            'type' => 'boolean',
            'position' => 10,
        ])->assertRedirect(route('questions.index'));
        $this->assertSame('0', Answer::sole()->value);
        $this->get(route('reports.answers', $question))->assertOk()->assertSee('Нет');

        $this->post(route('players.store'), [
            'questionnaire_revision' => $revision,
            'answers' => [$question->id => 'Нет'],
        ])->assertSessionHasErrors('questionnaire_revision');
        $this->assertDatabaseCount('players', 1);
    }
}
