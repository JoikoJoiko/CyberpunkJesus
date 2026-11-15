<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedInteger('position');
        });

        DB::table('questions')->orderBy('position')->orderBy('id')->pluck('block')
            ->map(fn ($name) => $name ?? '')->unique()->values()
            ->each(fn ($name, $index) => DB::table('question_blocks')->insert(['name' => $name, 'position' => ($index + 1) * 10]));
    }

    public function down(): void
    {
        Schema::dropIfExists('question_blocks');
    }
};
