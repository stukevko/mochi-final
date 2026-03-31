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
        // Attribut-Typen (z.B. Größe, Farbe)
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // z.B. "Größe", "Farbe"
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Attribut-Werte (z.B. S, M, L, XL für Größe)
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained()->onDelete('cascade');
            $table->string('value'); // z.B. "S", "M", "L", "XL" oder "Rot", "Blau"
            $table->string('color_code')->nullable(); // Für Farben: #FF0000
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Produkt-Varianten
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique()->nullable();
            $table->decimal('price', 10, 2)->nullable(); // Überschreibt Hauptpreis wenn gesetzt
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Verknüpfung: Variante <-> Attribut-Werte (z.B. Variante "T-Shirt Rot M")
        Schema::create('product_variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_attribute_value_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_variant_id', 'product_attribute_value_id'], 'variant_attribute_unique');
        });

        // Verknüpfung: Produkt <-> Welche Attribut-Typen hat das Produkt
        Schema::create('product_product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_attribute_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_id', 'product_attribute_id'], 'product_attribute_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_product_attributes');
        Schema::dropIfExists('product_variant_attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }
};
