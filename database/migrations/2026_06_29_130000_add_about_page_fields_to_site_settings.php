<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('about_page_title')->nullable();
            $table->string('about_hero_subtitle')->nullable();
            $table->text('about_intro')->nullable();
            $table->text('about_story')->nullable();
            $table->string('about_highlight_1_title')->nullable();
            $table->text('about_highlight_1_body')->nullable();
            $table->string('about_highlight_2_title')->nullable();
            $table->text('about_highlight_2_body')->nullable();
            $table->string('about_highlight_3_title')->nullable();
            $table->text('about_highlight_3_body')->nullable();
            $table->string('about_extra_title')->nullable();
            $table->text('about_extra_body')->nullable();
            $table->string('about_instagram_heading')->nullable();
            for ($i = 1; $i <= 5; $i++) {
                $table->string("about_gallery_image_{$i}")->nullable();
            }
            $table->string('about_cta_label')->nullable();
            $table->string('about_cta_url')->nullable();
            $table->string('about_meta_description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $columns = [
                'about_page_title',
                'about_hero_subtitle',
                'about_intro',
                'about_story',
                'about_highlight_1_title',
                'about_highlight_1_body',
                'about_highlight_2_title',
                'about_highlight_2_body',
                'about_highlight_3_title',
                'about_highlight_3_body',
                'about_extra_title',
                'about_extra_body',
                'about_instagram_heading',
                'about_gallery_image_1',
                'about_gallery_image_2',
                'about_gallery_image_3',
                'about_gallery_image_4',
                'about_gallery_image_5',
                'about_cta_label',
                'about_cta_url',
                'about_meta_description',
            ];

            $table->dropColumn($columns);
        });
    }
};
