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
        Schema::create('inquiry_reminders', function (Blueprint $table) {
         $table->id();
            $table->foreignId('inquirie_id')->constrained()->onDelete('cascade');
            $table->text('note');
            $table->text('reminder_type');
            $table->dateTime('reminder_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_reminders');
    }
};
