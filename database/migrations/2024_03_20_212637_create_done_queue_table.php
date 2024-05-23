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
        Schema::create('done_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_transaction')->constrained('transaction');
            $table->integer('nomor_antrian');
            $table->timestamp('done_at')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('done_queue');
    }
};
