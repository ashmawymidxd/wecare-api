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
        Schema::create('inquiries_time_lines', function (Blueprint $table) {
            $table->id();
            $table->string('stepOne');
            $table->string('stepTwo'); // Fixed: 'stepTow' to 'stepTwo'
            $table->string('stepThree');
            $table->foreignId('inquirie_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries_time_lines');
    }
};
