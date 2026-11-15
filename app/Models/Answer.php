<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    public $timestamps = false;

    protected $fillable = ['question_id', 'value', 'numeric_value'];

    #[Scope]
    protected function answered(Builder $query): void
    {
        $query->whereNotNull('value');
    }
}
