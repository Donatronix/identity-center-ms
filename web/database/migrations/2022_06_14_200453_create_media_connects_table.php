<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaConnectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_connects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id')->unique();
            $table->string('media_id')->nullable();
            $table->string('provider');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media_connects');
    }
}
