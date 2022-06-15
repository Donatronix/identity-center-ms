<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_users', function (Blueprint $table) {
            $table->char('id', 36);
            $table->char('auth_code', 6)->nullable();
            $table->string('identifier_chat', 40)->nullable();
            $table->char('bot_id', 36)->index('bot_id');
            $table->char('user_id', 36)->index('user_id');

            $table->primary(['id', 'bot_id', 'user_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreign('bot_id')->references('id')->on('bots')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bot_users');
    }
}
