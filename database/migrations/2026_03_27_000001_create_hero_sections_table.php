<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_sections', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('headline');
            $table->text('subheadline')->nullable();
            $table->string('background_image')->nullable();
            $table->string('cta_label')->default('Jetzt entdecken');
            $table->string('cta_url')->default('/shop');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_sections');
    }
};
