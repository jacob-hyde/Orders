<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentIntentRefundsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_intent_refunds', function (Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->integer('payment_intent_id')->unsigned();
			$table->string('refund_id');
			$table->string('reason');
			$table->string('status');
			$table->integer('amount');
			$table->timestamps();
			$table->softDeletes();
			$table->foreign('payment_intent_id')->references('id')->on('payment_intents');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_intent_refunds');
    }
}