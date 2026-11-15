<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class QuestionBlock extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'position'];

    public static function ordered(): Collection
    {
        return self::orderBy('position')->orderBy('id')->get();
    }

    public static function groupQuestions(Collection $questions): Collection
    {
        $groups = $questions->groupBy(fn (Question $question) => $question->block ?? '');
        $ordered = self::ordered()->pluck('name')->mapWithKeys(fn ($name) => [$name => $groups->get($name, collect())]);

        return $ordered->union($groups)->filter(fn ($group) => $group->isNotEmpty());
    }
}
