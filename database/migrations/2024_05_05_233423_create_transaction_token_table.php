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
        Schema::create('transaction_token', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->references('id')->on('transaction');
            $table->string('token');
            $table->boolean('is_used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_token');
    }
};
