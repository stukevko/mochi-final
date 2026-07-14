<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sumup_item_id')->nullable()->after('sku');
            $table->timestamp('sumup_synced_at')->nullable()->after('sumup_item_id');

            $table->index('sumup_item_id');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('sumup_item_id')->nullable()->after('sku');
            $table->timestamp('sumup_synced_at')->nullable()->after('sumup_item_id');

            $table->index('sumup_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['sumup_item_id']);
            $table->dropColumn(['sumup_item_id', 'sumup_synced_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['sumup_item_id']);
            $table->dropColumn(['sumup_item_id', 'sumup_synced_at']);
        });
    }
};
