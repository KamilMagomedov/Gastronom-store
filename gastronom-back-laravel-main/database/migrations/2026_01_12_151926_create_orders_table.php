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
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_amount', 8, 2)->default(0);
            $table->foreignId('delivery_method_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('delivery_cost', 8, 2)->nullable();
            $table->string('delivery_phone')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->string('delivery_street')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_apartment')->nullable();
            $table->string('delivery_postal_code')->nullable();
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            $table->string('delivery_building')->nullable();
            $table->string('delivery_entrance')->nullable();
            $table->string('delivery_floor')->nullable();
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status', 50)->default('pending');
            $table->string('payment_status', 50)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
