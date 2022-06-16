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
            $table->uuid('id')->primary();

            $table->char('auth_code', 6);
            $table->char('sid', 34)->nullable();

            $table->foreignUuid('user_id')->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->char('bot_id', 36)->nullable()->index('bot_id');

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
        Schema::dropIfExists('auth_codes');
    }
}
