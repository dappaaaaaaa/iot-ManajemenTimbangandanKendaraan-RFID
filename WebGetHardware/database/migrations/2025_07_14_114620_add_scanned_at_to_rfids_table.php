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
        Schema::table('rfids', function (Blueprint $table) {
            $table->timestamp(column: 'scanned_at')->nullable()->after('owner_name');
        });
    }

    public function down(): void
    {
        Schema::table('rfids', function (Blueprint $table) {
            $table->dropColumn('scanned_at');
        });
    }
};
