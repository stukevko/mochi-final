<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Shop-Einstellungen für White-Labeling und Konfiguration
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general'); // z.B. 'general', 'theme', 'payment', 'shipping'
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // 'string', 'boolean', 'integer', 'json', 'encrypted'
            $table->timestamps();
        });

        // Payment Gateway Konfigurationen
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // z.B. 'stripe', 'paypal', 'sumup', 'klarna', 'invoice'
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_test_mode')->default(true);
            $table->json('config')->nullable(); // API-Keys etc. (verschlüsselt gespeichert)
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('settings');
    }
};
