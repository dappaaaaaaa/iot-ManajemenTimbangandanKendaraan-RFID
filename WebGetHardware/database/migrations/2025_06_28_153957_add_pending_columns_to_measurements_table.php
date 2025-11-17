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
        if (!Schema::hasColumn('measurements', 'is_pending')) {
            $table->boolean('is_pending')->default(false);
        }

        if (!Schema::hasColumn('measurements', 'pending_changes')) {
            $table->jsonb('pending_changes')->nullable();
        }
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            //
        });
    }
};
