<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIntentsTable extends Migration
{
    public function up()
    {
        Schema::create('intents', function (Blueprint $table) {
			$table->increments('id')->unsigned();
            $table->string('uuid');
			$table->integer('api_client_id')->unsigned();
            $table->unsignedInteger('user_id')->nullable();
			$table->integer('order_id')->nullable()->unsigned();
			$table->string('step');
            $table->unsignedInteger('steppable_id');
            $table->string('steppable_type');
			$table->timestamps();
			$table->foreign('order_id')->references('id')->on('orders');
        });
    }

    public function down()
    {
        Schema::dropIfExists('intents');
    }
}