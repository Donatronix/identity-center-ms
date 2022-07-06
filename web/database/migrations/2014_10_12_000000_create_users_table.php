<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

            /**
             * User common data
             */
            $table->string('first_name', 60)->nullable();
            $table->string('last_name', 60)->nullable();
            $table->string('username', 25)->nullable()->unique();
            $table->enum('gender', [null, 'm', 'f'])->nullable();
            $table->date('birthday')->nullable();
            $table->unsignedBigInteger('phone')->nullable()->unique();
            $table->string('email', 150)->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->string('locale')->nullable();

            /**
             * User address information
             */
            $table->string('address_zip', 10)->nullable();
            $table->string('address_country', 150)->nullable();
            $table->string('address_city', 50)->nullable();
            $table->string('address_line1', 150)->nullable();
            $table->string('address_line2', 100)->nullable();

            /**
             * User identity document info
             */
            $table->string('id_number')->nullable(); // National identification number
            $table->string('document_number')->nullable();  // Document number
            $table->string('document_country', 3)->nullable(); // ISO-2- String Country that issued the document
            $table->tinyInteger('document_type')->default(0);  // Document type
            $table->mediumText('document_file')->nullable();  // Document file

            /**
             * SECURITY
             */
            $table->string('password', 60)->nullable();
            $table->rememberToken();
            $table->string('verify_token')->nullable();
            $table->string('verification_code')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            /**
             * User status and agreement
             */
            $table->boolean('is_agreement')->default(0);
            $table->unsignedTinyInteger('status')->default(User::STATUS_INACTIVE);
            $table->boolean('is_kyc_verified')->default(0);
            $table->boolean('subscribed_to_announcement')->default(false);

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
