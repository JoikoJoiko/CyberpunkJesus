<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    public static function ageFrom(?string $value): ?int
    {
        if ($value === null || $value > today()->toDateString()) {
            return null;
        }

        return (int) Carbon::parse($value)->diffInYears(today());
    }

    protected $fillable = ['nickname', 'notes', 'interviewed_on'];

    protected function casts(): array
    {
        return ['interviewed_on' => 'date', 'revision' => 'integer'];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
