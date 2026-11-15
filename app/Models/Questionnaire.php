<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Questionnaire
{
    public static function revision(bool $lock = false): int
    {
        $query = DB::table('questionnaire')->where('id', 1);

        return (int) ($lock ? $query->lockForUpdate() : $query)->value('revision');
    }

    public static function advance(): void
    {
        DB::table('questionnaire')->where('id', 1)->increment('revision');
    }
}
