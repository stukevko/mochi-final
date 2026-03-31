<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('hero_logo_path')->nullable();
            $table->string('hero_background_path')->nullable();
            $table->string('hero_headline')->nullable();
            $table->string('hero_subline')->nullable();
            $table->string('shop_cta_url')->nullable();
            $table->string('hero_learn_more_url')->nullable();

            for ($i = 1; $i <= 4; $i++) {
                $table->string("benefit_{$i}_title")->nullable();
                $table->text("benefit_{$i}_body")->nullable();
                $table->string("benefit_{$i}_image_path")->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $cols = [
                'hero_logo_path', 'hero_background_path', 'hero_headline', 'hero_subline',
                'shop_cta_url', 'hero_learn_more_url',
            ];
            for ($i = 1; $i <= 4; $i++) {
                $cols[] = "benefit_{$i}_title";
                $cols[] = "benefit_{$i}_body";
                $cols[] = "benefit_{$i}_image_path";
            }
            $table->dropColumn($cols);
        });
    }
};
