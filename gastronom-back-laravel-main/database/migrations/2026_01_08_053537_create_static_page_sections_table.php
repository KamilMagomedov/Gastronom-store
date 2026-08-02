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
        Schema::create('static_page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('static_page_id')->constrained()->onDelete('cascade');
            $table->integer('number');
            $table->string('title');
            $table->text('content');
            $table->text('important_note')->nullable();
            $table->timestamps();

            $table->index(['static_page_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('static_page_sections');
    }
};
