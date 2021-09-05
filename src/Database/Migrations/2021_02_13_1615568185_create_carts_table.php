<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
			$table->increments('id')->unsigned();
            $table->string('uuid');
			$table->integer('api_client_id')->unsigned();
            $table->unsignedInteger('user_id')->nullable();
			$table->integer('order_id')->nullable()->unsigned();
			$table->string('cartable_type');
            $table->unsignedInteger('cartable_id');
            $table->json('meta')->nullable();
            $table->tinyInteger('days_sent')->nullable();
			$table->timestamps();
            $table->softDeletes();
			$table->foreign('order_id')->references('id')->on('orders');
        });
    }

    public function down()
    {
        Schema::dropIfExists('carts');
    }
}