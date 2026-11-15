<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
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
