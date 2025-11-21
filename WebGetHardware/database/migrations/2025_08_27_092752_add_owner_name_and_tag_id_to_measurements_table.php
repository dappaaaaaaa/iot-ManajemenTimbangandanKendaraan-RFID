<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->string('owner_name')->nullable()->after('user_id');
            $table->string('tag_id')->nullable()->after('owner_name');
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->dropColumn(['owner_name', 'tag_id']);
        });
    }
};
