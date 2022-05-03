<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePublisherMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('publisher_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uniq_id');
            $table->string('queue');
            $table->string('event');
            $table->mediumText('message');
            //$table->string('exchange');
            $table->text('properties')->nullable();
            $table->tinyInteger('status')->default(0);
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
        Schema::dropIfExists('publisher_messages');
    }
}
