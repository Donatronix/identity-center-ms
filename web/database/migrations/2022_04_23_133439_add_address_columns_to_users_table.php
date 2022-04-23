<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address_zip', 10)->nullable()->after('password');
            $table->string('address_city', 50)->nullable()->after('password');
            $table->string('address_line2', 100)->nullable()->after('password');
            $table->string('address_line1', 150)->nullable()->after('password');
            $table->string('address_country', 3)->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('address_country');
            $table->dropColumn('address_line1');
            $table->dropColumn('address_line2');
            $table->dropColumn('address_city');
            $table->dropColumn('address_zip');
        });
    }
}
