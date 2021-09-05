<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorPayoutsTable extends Migration
{
    public function up()
    {
        Schema::create('vendor_payouts', function (Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->integer('vendor_id')->unsigned();
			$table->integer('payment_id')->unsigned()->nullable();
			$table->integer('order_id')->unsigned();
			$table->integer('amount')->unsigned();
			$table->boolean('paid', 1)->default(0);
			$table->timestamps();
			$table->foreign('order_id')->references('id')->on('orders');
			$table->foreign('payment_id')->references('id')->on('payments');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_payouts');
    }
}