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
            $table->text('description')->nullable()->after('action');
        });
    }

    public function down(): void
    {
        Schema::table('measurement_histories', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
