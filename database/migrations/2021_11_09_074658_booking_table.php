<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BookingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('booking', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->date('checkin')->nullable();
            $table->date('checkout')->nullable();
            $table->tinyInteger('adults')->default(1);
            $table->tinyInteger('children')->default(0);
            $table->tinyInteger('quota')->default(1);
            $table->string('first_name', 255)->default('');
            $table->string('last_name', 255)->default('');
            $table->string('email', 255)->default('');
            $table->string('phone', 20)->default('');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists('booking');
    }
}
