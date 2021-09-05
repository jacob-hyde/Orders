<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartItemsTable extends Migration
{
    public function up()
    {
        Schema::create('cart_items', function (Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->unsignedInteger('cart_id');
            $table->string('cart_itemable_type');
            $table->unsignedInteger('cart_itemable_id');
            $table->string('type')->nullable();
			$table->timestamps();
            $table->foreign('cart_id')->references('id')->on('carts')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart_items');
    }
}