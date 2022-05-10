<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name', 60)->nullable();
            $table->string('last_name', 60)->nullable();
            $table->enum('gender', [null, 'm', 'f'])->nullable();
            $table->string('id_number')->nullable(); // National identification number
            $table->string('username', 25)->nullable()->unique();
            $table->unsignedBigInteger('phone')->unique();
            $table->string('email', 150)->nullable()->unique();
            $table->date('birthday')->nullable();
            $table->string('password', 60)->nullable();
            $table->tinyInteger('status')->default(User::STATUS_INACTIVE);
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            // User address information
            $table->string('address_zip', 10)->nullable();
            $table->string('address_city', 50)->nullable();
            $table->string('address_line2', 100)->nullable();
            $table->string('address_line1', 150)->nullable();
            $table->string('address_country', 3)->nullable();

            $table->string('verification_code')->nullable();


            /**
             * User document infp
             */
            $table->string('document_number')->nullable();  // Document number
            $table->string('document_country', 3)->nullable(); // ISO-2- String Country that issued the document
            $table->tinyInteger('document_type')->default(0);  // Document type
            $table->text('document_file')->nullable();  // Document file

            $table->boolean('subscribed_to_announcement')->default(false);
            $table->string('verify_token')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
