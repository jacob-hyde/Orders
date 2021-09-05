<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentIntentsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_intents', function (Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->integer('buyer_id')->unsigned();
			$table->integer('seller_id')->unsigned()->nullable();
			$table->string('intent_id');
			$table->string('client_secret')->nullable();
			$table->integer('amount');
			$table->integer('fee');
			$table->string('customer')->nullable();
			$table->json('meta')->nullable();
			$table->string('status');
			$table->string('originating_transaction_id', 255)->nullable();
            $table->string('seller_stripe_id', 255)->nullable();
            $table->string('application_fee_stripe_id', 255)->nullable();
            $table->string('application_fee_id', 255)->nullable();
            $table->string('on_behalf_of', 255)->nullable();
            $table->string('transfer_data_destination', 255)->nullable();
            $table->string('transfer_id', 255)->nullable();
            $table->string('transfer_status', 255)->nullable();
            $table->float('application_fee_amount')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_intents');
    }
}