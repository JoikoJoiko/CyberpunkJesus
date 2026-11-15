<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public const BIRTH_DATE_CODE = 'birthDate';

    protected $fillable = ['label', 'type', 'options', 'position', 'code', 'block'];

    protected function casts(): array
    {
        return ['type' => QuestionType::class, 'options' => 'array'];
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function displayAnswer(string $answer): string
    {
        if ($this->type === QuestionType::Boolean) {
            return $answer === '1' ? 'Да' : 'Нет';
        }

        return $answer;
    }
}
