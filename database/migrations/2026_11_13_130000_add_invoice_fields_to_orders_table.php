<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fortlaufende Rechnungsnummer (§ 14 UStG) + Rechnungs-/Zahlungsdatum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('invoice_number', 64)->nullable()->unique()->after('order_number');
            $table->timestamp('invoiced_at')->nullable()->after('invoice_number');
            $table->timestamp('paid_at')->nullable()->after('invoiced_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn(['invoice_number', 'invoiced_at', 'paid_at']);
        });
    }
};
