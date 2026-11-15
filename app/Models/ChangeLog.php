<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id', 'actor_name', 'actor_email', 'subject_type', 'subject_id',
        'subject_label', 'operation', 'fields',
    ];

    protected function casts(): array
    {
        return ['fields' => 'array'];
    }

    public static function record(User $actor, Player|Question $subject, string $operation, array $fields = []): void
    {
        $isPlayer = $subject instanceof Player;

        self::create([
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'subject_type' => $isPlayer ? 'player' : 'question',
            'subject_id' => $subject->id,
            'subject_label' => $isPlayer ? ($subject->nickname ?? 'Без ника') : $subject->label,
            'operation' => $operation,
            'fields' => $fields,
        ]);
    }
}
