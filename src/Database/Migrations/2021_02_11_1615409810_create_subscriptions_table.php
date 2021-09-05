<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
			$table->bigIncrements('id')->unsigned();
			$table->bigInteger('user_id')->unsigned();
			$table->string('name');
			$table->string('stripe_id');
			$table->string('stripe_status');
			$table->string('stripe_plan');
			$table->integer('quantity');
			$table->timestamp('trial_ends_at')->nullable();
			$table->timestamp('ends_at')->nullable();
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}