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

            $table->string('id_doctype');  // Document type
            $table->string('address_verify_doctype');  // Document type

            $table->longText('id_document')->nullable(); // ID document file
            $table->longText('address_verify_document')->nullable(); //Address verification document file
            $table->longText('portrait')->nullable(); // Selfie

            $table->foreignUuid('user_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->enum('status', KYC::$statuses)->default(KYC::STATUS_PENDING);

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
        Schema::dropIfExists('k_y_c_s');
    }
}
