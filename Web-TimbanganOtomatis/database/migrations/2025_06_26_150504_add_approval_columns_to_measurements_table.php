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
        Schema::table('measurements', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->boolean('is_pending')->default(false); // true jika hasil edit belum disetujui
            $table->boolean('is_approved')->nullable();    // true = acc, false = ditolak, null = belum diproses
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
