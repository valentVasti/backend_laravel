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
        Schema::create('temp_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_transaction')->nullable()->default(null)->constrained('transaction');
            $table->foreignId('id_mesin')->constrained('mesin');
            $table->integer('nomor_antrian');
            $table->enum('layanan', ['CUCI', 'KERING', 'KOMPLIT']);
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_queue');
    }
};
