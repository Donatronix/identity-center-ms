<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\KYC;

class CreateKYCSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('k_y_c_s', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('id_number')->nullable(); // National identification number
            $table->string('document_number')->nullable();  // Document number
            $table->string('document_country', 3)->nullable(); // ISO-2- String Country that issued the document
            $table->tinyInteger('document_type')->default(0);  // Document type
            $table->longText('document_file')->nullable();  // Document file
            $table->longText('document_back')->nullable();  // Document file
            $table->enum('status', KYC::$statuses)->default(KYC::STATUS_PENDING);

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
        Schema::dropIfExists('k_y_c_s');
    }
}
