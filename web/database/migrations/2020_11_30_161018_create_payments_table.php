<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('amount');
            $table->char('currency', 3);


            $table->tinyInteger('type');
            $table->string('gateway', 10);
            $table->string('document_id')->nullable();
            $table->string('service', 36)->nullable();
            $table->smallInteger('status')->nullable();
            $table->boolean('transaction_created')->default(false);

            $table->uuid('user_id')->index();

            $table->string('check_code');
            $table->text('payload')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

//            $table->tinyInteger('payment_method');
//            $table->tinyInteger('payment_system');
//            $table->unsignedTinyInteger('payment_currency_id')->nullable();

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
