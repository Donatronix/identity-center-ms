<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContributorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contributors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Contributor common data
             */
            $table->string('first_name', 60)->nullable();
            $table->string('last_name', 60)->nullable();
            $table->enum('gender', [null, 'm', 'f'])->nullable();
            $table->date('date_birthday')->nullable(); // (YYYY-MM-DD) Date of birth
            $table->string('email', 100)->nullable();
            $table->string('id_number')->nullable();        // National identification number

            /**
             * Contributor address
             */
            $table->string('address_country', 3)->nullable();
            $table->string('address_line1', 150)->nullable();
            $table->string('address_line2', 100)->nullable();
            $table->string('address_city', 50)->nullable();
            $table->string('address_zip', 10)->nullable();

            /**
             * Contributor document
             */
            $table->string('document_number')->nullable();  // Document number
            $table->string('document_country', 3)->nullable(); // ISO-2- String Country that issued the document
            $table->tinyInteger('document_type')->default(0);  // Document type
            $table->text('document_file')->nullable();  // Document file

            /**
             * Contributor status and agreement
             */
            $table->boolean('is_agreement')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->boolean('is_verified')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whitelists');
    }
}
