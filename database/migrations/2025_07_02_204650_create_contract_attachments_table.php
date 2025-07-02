<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contract_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->enum('type', ['contract', 'payment_proof']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_attachments');
    }
};
