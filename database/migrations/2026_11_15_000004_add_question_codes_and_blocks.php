<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('code', 64)->nullable();
            $table->string('block', 100)->nullable();
        });

        foreach (DB::table('questions')->select('id')->lazyById() as $question) {
            DB::table('questions')->where('id', $question->id)->update(['code' => 'question_'.$question->id]);
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->string('code', 64)->nullable(false)->change();
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'block']);
        });
    }
};
