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
        Schema::table('done_queue', function (Blueprint $table) {
            $table->foreignId('pengering')->after('id_transaction')->nullable()->default(null)->constrained('mesin');
            $table->foreignId('pencuci')->after('id_transaction')->nullable()->default(null)->constrained('mesin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('done_queue', function (Blueprint $table) {
            //
        });
    }
};
