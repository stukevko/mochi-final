<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Protokoll der Ladenverkäufe aus SumUp (Kasse), die bereits im Shop-Bestand verbucht wurden.
 * Verhindert doppeltes Abziehen und zeigt im Admin, was abgeglichen wurde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sumup_sales', function (Blueprint $table): void {
            $table->id();
            $table->string('sale_id', 100)->unique();
            $table->string('channel', 40)->nullable();
            $table->integer('total_cents')->default(0);
            $table->timestamp('closed_at')->nullable()->index();
            $table->unsignedInteger('lines_matched')->default(0);
            $table->unsignedInteger('lines_unmatched')->default(0);
            $table->json('lines')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sumup_sales');
    }
};
