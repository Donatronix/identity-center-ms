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
            // need username to be case insensitive
            $table->string('username', 25)->collation("latin_general_ci")->nullable()->unique();
            $table->bigInteger('phone_number')->unsigned()->unique();
            $table->timestamp('phone_number_verified_at')->nullable();
            $table->string('email', 150)->nullable()->unique();
            $table->date('birthday')->nullable();
            $table->string('password', 60)->nullable();
            $table->tinyInteger('status')->default(User::STATUS_INACTIVE);
            $table->timestamp('email_verified_at')->nullable();
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
