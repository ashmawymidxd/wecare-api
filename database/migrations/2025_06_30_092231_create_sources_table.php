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
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->string('nationality')->nullable();
            $table->string('preferred_language')->nullable();
            $table->foreignId('account_manager_id')->nullable()->constrained('employees');
            $table->date('last_connect_date')->nullable();
            $table->integer('clients_number')->default(0);
            $table->enum('source_type', [
                'Tasheel',
                'Typing Center',
                'PRO',
                'Social Media',
                'Referral',
                'Inactive'
            ])->default('Inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
