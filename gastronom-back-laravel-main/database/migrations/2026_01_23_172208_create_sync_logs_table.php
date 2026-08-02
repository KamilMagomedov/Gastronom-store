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
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source'); // 1C, API, etc.
            $table->string('entity_type'); // products, orders, customers, etc.
            $table->unsignedBigInteger('entity_id')->nullable(); // ID of synced entity
            $table->string('operation'); // create, update, delete, sync
            $table->enum('status', ['success', 'error'])->default('success');
            $table->text('message')->nullable(); // Success message or error details
            $table->json('data')->nullable(); // Synced data (for debugging)
            $table->timestamp('synced_at')->useCurrent();
            $table->index(['source', 'entity_type', 'status']);
            $table->index(['synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
