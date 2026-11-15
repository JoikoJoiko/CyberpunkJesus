<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->text('value')->nullable()->change();
            $table->text('clarification')->nullable();
        });
    }

    public function down(): void
    {
        DB::table('answers')->whereNull('value')->delete();

        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn('clarification');
            $table->text('value')->nullable(false)->change();
        });
    }
};
