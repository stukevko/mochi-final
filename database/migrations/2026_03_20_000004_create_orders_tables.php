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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Bestellstatus
            $table->enum('status', [
                'pending',      // Ausstehend
                'processing',   // In Bearbeitung
                'shipped',      // Versendet
                'delivered',    // Geliefert
                'cancelled',    // Storniert
                'refunded'      // Erstattet
            ])->default('pending');
            
            // Zahlungsstatus
            $table->enum('payment_status', [
                'pending',      // Ausstehend
                'paid',         // Bezahlt
                'failed',       // Fehlgeschlagen
                'refunded',     // Erstattet
                'cancelled'     // Storniert
            ])->default('pending');
            
            // Zahlungsmethode (Strategy Pattern - wird später vom Payment Gateway genutzt)
            $table->string('payment_method')->nullable(); // z.B. 'stripe', 'paypal', 'sumup', 'klarna', 'invoice'
            $table->string('payment_id')->nullable();     // Externe Transaktions-ID vom Payment Provider
            $table->json('payment_data')->nullable();     // Zusätzliche Zahlungsdaten (Provider-spezifisch)
            
            // Beträge
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            
            // Adressen (JSON für Flexibilität)
            $table->json('billing_address');
            $table->json('shipping_address')->nullable();
            
            // Sonstiges
            $table->text('notes')->nullable();
            $table->string('currency', 3)->default('EUR');
            
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('set null');
            
            // Snapshot der Produktdaten zum Bestellzeitpunkt
            $table->string('product_name');
            $table->string('variant_name')->nullable(); // z.B. "Rot, M"
            $table->string('sku')->nullable();
            
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
