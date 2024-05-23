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
        Schema::create('open_close_queue_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id_opener')->references('id')->on('users')->nullable();
            $table->foreignId('user_id_closer')->nullable()->default(null)->references('id')->on('users');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_close_queue_log');
    }
};
