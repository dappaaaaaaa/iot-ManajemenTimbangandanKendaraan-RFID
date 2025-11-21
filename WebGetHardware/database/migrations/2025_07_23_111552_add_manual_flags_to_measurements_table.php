<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->boolean('is_manual_gross_mine')->default(false)->after('gross_at_mine');
            $table->boolean('is_manual_tare_mine')->default(false)->after('tare_at_mine');
            $table->boolean('is_manual_gross_jetty')->default(false)->after('gross_at_jetty');
            $table->boolean('is_manual_tare_jetty')->default(false)->after('tare_at_jetty');
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->dropColumn([
                'is_manual_gross_mine',
                'is_manual_tare_mine',
                'is_manual_gross_jetty',
                'is_manual_tare_jetty',
            ]);
        });
    }
};
