<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMeasurementsTable extends Migration
{
    public function up()
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('vehicle_number');
            $table->decimal('gross_at_mine', 15, 2)->nullable();
            $table->decimal('tare_at_mine', 15, 2)->nullable();
            $table->decimal('gross_at_jetty', 15, 2)->nullable();
            $table->decimal('tare_at_jetty', 15, 2)->nullable();
            $table->timestamp('mine_entry_time')->nullable();
            $table->timestamp('mine_exit_time')->nullable();
            $table->timestamp('houling_exit_time')->nullable();
            $table->timestamp('jetty_entry_time')->nullable();
            $table->timestamp('jetty_exit_time')->nullable();

            // Menggunakan ENUM untuk status
            $table->enum('measurement_status', ['on_going', 'completed'])->default('on_going');

            $table->timestamps();
            $table->decimal('gross_at_hauling', 15, 2)->nullable();
            $table->decimal('net_at_hauling', 15, 2)->nullable();
            $table->decimal('gross_at_houling', 15, 2)->nullable();
            $table->decimal('tare_at_houling', 15, 2)->nullable();
            $table->string('attachments', 255)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('measurements');
    }
}
