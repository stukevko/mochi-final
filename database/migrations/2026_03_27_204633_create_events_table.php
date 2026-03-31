<?php

use App\Enums\EventStatus;
use App\Enums\GameType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('game_type')->default(GameType::Pokemon->value);
            $table->string('image_path')->nullable();
            $table->string('status')->default(EventStatus::Active->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
