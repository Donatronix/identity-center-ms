<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVerifyStepInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('verify_step_infos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username');
            $table->string('channel');
            $table->string('receiver');
            $table->string('code')->unique();
            $table->string("validity")->nullable();
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
        Schema::dropIfExists('verify_step_infos');
    }
}
