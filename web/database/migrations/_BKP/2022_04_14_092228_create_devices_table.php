<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('user_id', 36)->nullable()->index('user_id');
            $table->string('name', 100);
            $table->string('identifier', 100);
            $table->integer('ip')->nullable();
            $table->dateTime('created_at')->nullable();

            $table->foreign(['user_id'], 'devices_ibfk_1')->references(['id'])->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devices');
    }
}
