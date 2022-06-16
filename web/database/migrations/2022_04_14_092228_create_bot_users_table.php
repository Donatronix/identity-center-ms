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
            $table->uuid('id')->primary();

            $table->char('auth_code', 6)->nullable();
            $table->string('identifier_chat', 40)->nullable();

            $table->char('bot_id', 36)->index('bot_id');

            $table->foreignUuid('user_id')->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->timestamps();
            //$table->softDeletes();
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
