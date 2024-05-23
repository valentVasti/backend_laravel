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
        Schema::create('failed_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->references('id')->on('transaction');
            $table->integer('nomor_antrian');
            $table->timestamp('failed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_queue');
    }
};
