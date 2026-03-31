<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index(['is_active', 'category_id'], 'products_active_category_idx');
            $table->index(['is_active', 'created_at'], 'products_active_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_active_category_idx');
            $table->dropIndex('products_active_created_idx');
        });
    }
};
