<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuthCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auth_codes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->char('auth_code', 6);
            $table->char('sid', 34)->nullable();
            $table->char('user_id', 36);
            $table->dateTime('created')->nullable();
            $table->char('bot_id', 36)->nullable()->index('bot_id');

            $table->foreign(['bot_id'], 'auth_codes_ibfk_1')->references(['id'])->on('bots');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auth_codes');
    }
}
