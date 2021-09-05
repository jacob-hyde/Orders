<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaypalPayoutsTable extends Migration
{
    public function up()
    {
        Schema::create('paypal_payouts', function (Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->integer('user_id')->unsigned();
			$table->string('paypal_email');
			$table->string('status');
			$table->integer('amount');
			$table->string('payout_batch_id');
			$table->string('email_subject');
			$table->text('note');
			$table->timestamps();
			$table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypal_payouts');
    }
}