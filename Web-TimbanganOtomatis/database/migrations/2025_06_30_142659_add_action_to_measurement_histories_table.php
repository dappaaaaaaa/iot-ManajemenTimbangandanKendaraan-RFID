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
        Schema::table('measurement_histories', function (Blueprint $table) {
            $table->string('action')->nullable(); // atau text jika lebih panjang
        });
    }

    public function down(): void
    {
        Schema::table('measurement_histories', function (Blueprint $table) {
            $table->dropColumn('action');
        });
    }
};
