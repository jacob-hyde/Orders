<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->string('key')->nullable();
			$table->integer('processor_id')->unsigned()->nullable();
			$table->string('processor_type')->nullable();
			$table->integer('buyer_user_id')->unsigned();
			$table->integer('seller_user_id')->unsigned()->nullable();
			$table->integer('amount')->nullable();
			$table->integer('fee')->nullable();
			$table->enum('status',['pending','paid','declined','refunded','partial-refunded'])->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}