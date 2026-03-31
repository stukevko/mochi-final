<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('description')->nullable()->after('type');
            $table->boolean('is_encrypted')->default(false)->after('description');
        });
        
        // Aktualisiere bestehende type-Werte um konsistent zu sein
        // 'string' -> 'text', 'integer' -> 'number'
        DB::table('settings')->where('type', 'string')->update(['type' => 'text']);
        DB::table('settings')->where('type', 'integer')->update(['type' => 'number']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rückwandlung der type-Werte
        DB::table('settings')->where('type', 'text')->update(['type' => 'string']);
        DB::table('settings')->where('type', 'number')->update(['type' => 'integer']);
        
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['description', 'is_encrypted']);
        });
    }
};
