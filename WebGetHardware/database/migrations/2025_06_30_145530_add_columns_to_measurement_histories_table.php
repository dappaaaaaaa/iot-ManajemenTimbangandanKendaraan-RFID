<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('measurement_histories', function (Blueprint $table) {
            // Jangan tambahkan kolom yang sudah ada
            // $table->text('description')->nullable();
            // $table->string('action')->nullable(); // juga sudah ada, jangan tambahkan

            if (!Schema::hasColumn('measurement_histories', 'old_values')) {
                $table->json('old_values')->nullable();
            }

            if (!Schema::hasColumn('measurement_histories', 'new_values')) {
                $table->json('new_values')->nullable();
            }

            if (!Schema::hasColumn('measurement_histories', 'changed_fields')) {
                $table->json('changed_fields')->nullable();
            }

            if (!Schema::hasColumn('measurement_histories', 'action_note')) {
                $table->text('action_note')->nullable();
            }

            if (!Schema::hasColumn('measurement_histories', 'measurement_id')) {
                $table->foreignId('measurement_id')->nullable()->constrained()->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('measurement_histories', function (Blueprint $table) {
            $table->dropColumn([
                // Jangan drop kolom yang tidak ditambahkan oleh migrasi ini
                // 'description',
                // 'action',
                'old_values',
                'new_values',
                'changed_fields',
                'action_note',
                'measurement_id',
            ]);
        });
    }
};
