<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecoveryQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recovery_questions', function (Blueprint $table) {
<<<<<<< HEAD
            $table->uuid('id')->primary();
            //$table->bigIncrements('id');
=======
            $table->bigIncrements('id');
>>>>>>> 15786b3c85293ea1910b173a5fa49b3a979e525e
            $table->uuid('user_id')->unique();
            $table->string('question_one')->default('What is my middle name');
            $table->string('answer_one');
            $table->string('question_two')->default('What is my pets name');
            $table->string('answer_two');
            $table->string('question_three')->default('Where is the village');
            $table->string('answer_three');

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
        Schema::dropIfExists('recovery_questions');
    }
}
