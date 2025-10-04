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
        Schema::create('source_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->onDelete('cascade');
            $table->text('note');
            $table->foreignId('added_by')->constrained('employees')->onDelete('cascade');
            $table->timestamp('date_added');
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['source_id', 'date_added']);
            $table->index('added_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_notes');
    }
};
