<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rfid_temp_scans', function (Blueprint $table) {
            $table->id();
            $table->string('tag_id')->unique();
            $table->timestamp('scanned_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_temp_scans');
    }
};
