<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentablesTable extends Migration
{
    public function up()
    {
        Schema::create('paymentables', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('payment_id')->unsigned();
            $table->integer('paymentable_id')->unsigned();
            $table->string('paymentable_type');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('payment_id')->references('id')->on('payments');
        });
    }

    public function down()
    {
        Schema::dropIfExists('paymentables');
    }
}