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
        Schema::create('rfids', function (Blueprint $table) {
            $table->id();
            $table->string('tag_id')->unique();
            $table->string('owner_name')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp(column: 'scanned_at')->nullable()->after('owner_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfids');
    }
};
