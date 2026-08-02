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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('price_representation')->nullable();
            $table->decimal('old_price', 10, 2)->nullable();
            $table->string('old_price_representation')->nullable();
            $table->string('sku')->unique()->nullable();
            $table->string('external_id')->unique()->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('in_stock')->default(true);
            $table->boolean('is_active')->default(true);
            $table->decimal('weight', 8, 3)->nullable(); // in kg
            $table->string('unit')->default('шт'); // единица измерения: шт, кг, л, м, etc.
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['sku']);
            $table->index(['external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
