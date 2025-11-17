<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('measurement_histories', function (Blueprint $table) {
            $table->string('field')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('measurement_histories', function (Blueprint $table) {
            $table->string('field')->nullable(false)->change();
        });
    }
};
