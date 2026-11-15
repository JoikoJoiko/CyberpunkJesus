<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('revision')->default(1);
        });
        DB::table('questionnaire')->insert(['id' => 1]);

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('nickname', 100)->nullable()->index();
            $table->string('server', 100)->nullable()->index();
            $table->text('notes')->nullable();
            $table->date('interviewed_on')->nullable()->index();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('label', 180);
            $table->string('type', 20);
            $table->json('options')->nullable();
            $table->unsignedInteger('position')->default(100);
            $table->timestamps();
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('value');
            $table->decimal('numeric_value', 15, 6)->nullable();
            $table->unique(['player_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('players');
        Schema::dropIfExists('questionnaire');
    }
};
