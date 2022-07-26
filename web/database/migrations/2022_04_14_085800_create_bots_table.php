<?php

use App\Models\Channel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 200); //->unique();
            $table->string('number', 100)->nullable();
            $table->string('type', '50');

            // 'webhook_url': f'url.{request.param[1]}',
            // 'is_active': bool(random() < 0.5)  # random true or false

            $table->timestamps();
        });
    }

// auth_code = relationship("AuthCode", back_populates="bot", cascade="all, delete-orphan")

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bots');
    }
}
