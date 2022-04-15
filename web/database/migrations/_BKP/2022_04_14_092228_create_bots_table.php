<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name', 100);
            $table->string('uri', 100);
            $table->string('token', 200)->unique('token');
            $table->enum('platform', ['SUMRA', 'ULTAINFINITY']);
            $table->enum('type', ['TELEGRAM', 'VIBER', 'LINE', 'DISCORD', 'SIGNAL', 'WHATSAPP', 'TWILIO', 'NEXMO']);
            $table->unsignedTinyInteger('status');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bots');
    }
}
