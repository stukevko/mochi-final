<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index(['is_active', 'price'], 'products_active_price_idx');
            $table->index(['is_active', 'sale_price'], 'products_active_sale_price_idx');
        });

        Schema::table('events', function (Blueprint $table): void {
            // Controller: active() -> where status, then order/paginate by starts_at,
            // optionally filter by game_type.
            $table->index(['status', 'starts_at'], 'events_status_starts_at_idx');
            $table->index(['status', 'game_type', 'starts_at'], 'events_status_game_starts_idx');
        });

        Schema::table('posts', function (Blueprint $table): void {
            // PostController: published() -> is_published + published_at range,
            // then orderByDesc(published_at) and filters by category/type.
            $table->index(['is_published', 'published_at'], 'posts_published_at_idx');
            $table->index(['post_category_id', 'published_at'], 'posts_category_published_at_idx');
            $table->index(['type', 'published_at'], 'posts_type_published_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_active_price_idx');
            $table->dropIndex('products_active_sale_price_idx');
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex('events_status_starts_at_idx');
            $table->dropIndex('events_status_game_starts_idx');
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex('posts_published_at_idx');
            $table->dropIndex('posts_category_published_at_idx');
            $table->dropIndex('posts_type_published_at_idx');
        });
    }
};

