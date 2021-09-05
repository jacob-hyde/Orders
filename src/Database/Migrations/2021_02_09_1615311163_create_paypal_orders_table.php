<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaypalOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('paypal_orders', function (Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->integer('buyer_user_id')->unsigned();
			$table->integer('seller_user_id')->unsigned()->nullable();
			$table->string('order_id');
			$table->string('capture_id')->nullable();
			$table->string('refund_id')->nullable();
			$table->string('payer_id')->nullable();
			$table->string('payment_link');
			$table->string('status');
			$table->integer('amount');
			$table->integer('fee')->default(0);
			$table->timestamps();
			$table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypal_orders');
    }
}