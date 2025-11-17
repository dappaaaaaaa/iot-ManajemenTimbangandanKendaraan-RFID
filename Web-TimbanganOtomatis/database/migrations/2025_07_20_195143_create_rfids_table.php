<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rfids', function (Blueprint $table) {
            $table->id(); // SERIAL PRIMARY KEY
            $table->string('tag_id')->unique(); // UID dari kartu RFID
            $table->string('owner_name')->nullable(); // Nama Supir
            $table->string('vehicle_number')->nullable(); // Nomor kendaraan
            $table->enum('status', ['pending', 'active'])->default('pending'); // Status kartu
            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfids');
    }
};
