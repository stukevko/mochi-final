<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('hero_sales_teaser')->nullable();
            $table->string('featured_product_id')->nullable();
            $table->string('featured_product_title')->nullable();
            $table->string('featured_product_price', 64)->nullable();
            $table->string('featured_product_url', 2048)->nullable();
            $table->string('featured_product_image_path')->nullable();

            for ($i = 1; $i <= 4; $i++) {
                $table->string("benefit_{$i}_icon", 32)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $cols = [
                'hero_sales_teaser',
                'featured_product_id',
                'featured_product_title',
                'featured_product_price',
                'featured_product_url',
                'featured_product_image_path',
            ];
            for ($i = 1; $i <= 4; $i++) {
                $cols[] = "benefit_{$i}_icon";
            }
            $table->dropColumn($cols);
        });
    }
};
