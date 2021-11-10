<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('adults')->default(1);
            $table->tinyInteger('children')->default(0);
            $table->date('date')->nullable();
            $table->integer('price')->default(0);
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
        Schema::dropIfExists('prices');
    }
}
