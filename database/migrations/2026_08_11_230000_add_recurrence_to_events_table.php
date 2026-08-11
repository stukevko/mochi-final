<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('recurrence')->default('none')->after('starts_at');
            $table->date('recurrence_until')->nullable()->after('recurrence');
            $table->foreignId('parent_event_id')
                ->nullable()
                ->after('recurrence_until')
                ->constrained('events')
                ->cascadeOnDelete();
            $table->index(['parent_event_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_event_id');
            $table->dropColumn(['recurrence', 'recurrence_until']);
        });
    }
};
