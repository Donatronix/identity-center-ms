<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPortraitToKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('k_y_c_s', function (Blueprint $table) {
            $table->longText('portrait')->nullable();
            $table->renameColumn('document_file', 'document_front');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('k_y_c_s', function (Blueprint $table) {
            $table->dropColumn('portrait');
            $table->renameColumn('document_front', 'document_file');
        });
    }
}
