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
        Schema::create('pending_rfid_tags', function (Blueprint $table) {
            $table->id();
            $table->string('tag_id')->unique(); // ID Tag RFID yang sedang menunggu registrasi
            $table->timestamp('scanned_at')->nullable(); // Waktu terakhir discan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_rfid_tags');
    }
};
