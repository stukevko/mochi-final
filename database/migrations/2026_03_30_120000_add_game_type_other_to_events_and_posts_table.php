<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('game_type_other', 120)->nullable()->after('game_type');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('game_type_other', 120)->nullable()->after('game_type');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('game_type_other');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('game_type_other');
        });
    }
};
