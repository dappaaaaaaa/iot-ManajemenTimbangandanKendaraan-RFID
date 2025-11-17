<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop kolom lama terlebih dahulu (jika sudah ada)
        Schema::table('measurement_histories', function (Blueprint $table) {
            $table->dropForeign(['measurement_id']);
            $table->dropColumn('measurement_id');
        });

        // Tambah kolom dengan tipe UUID dan relasi ulang
        Schema::table('measurement_histories', function (Blueprint $table) {
            $table->uuid('measurement_id')->nullable()->after('id');
            $table->foreign('measurement_id')->references('id')->on('measurements')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('measurement_histories', function (Blueprint $table) {
            $table->dropForeign(['measurement_id']);
            $table->dropColumn('measurement_id');

            $table->foreignId('measurement_id')->nullable()->constrained()->onDelete('set null');
        });
    }
};
