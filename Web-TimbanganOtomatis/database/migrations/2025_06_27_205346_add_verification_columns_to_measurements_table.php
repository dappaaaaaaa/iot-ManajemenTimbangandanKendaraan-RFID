<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->boolean('is_pending')->default(false)->after('measurement_status');
            $table->boolean('is_approved')->nullable()->after('is_pending'); // true = acc, false = tolak
            $table->unsignedBigInteger('edited_by')->nullable()->after('user_id');

            // Jika kamu ingin relasi ke users
            $table->foreign('edited_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->dropForeign(['edited_by']);
            $table->dropColumn(['is_pending', 'is_approved', 'edited_by']);
        });
    }
};
