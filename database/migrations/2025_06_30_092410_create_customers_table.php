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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('mobile');
            $table->string('email')->nullable();
            $table->string('nationality')->nullable();
            $table->string('preferred_language')->default('en');
            $table->text('address')->nullable();
            $table->string('company_name')->nullable();
            $table->string('business_category')->nullable();
            $table->string('country')->nullable();
            $table->date('joining_date')->nullable();
            $table->enum('source_type', [
                'Tasheel',
                'Typing Center',
                'PRO',
                'Social Media',
                'Referral',
                'Inactive'
            ])->nullable();
            $table->enum('status', [
               "Active", "Inactive", "New"
            ])->nullable();
            $table->string('profile_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
