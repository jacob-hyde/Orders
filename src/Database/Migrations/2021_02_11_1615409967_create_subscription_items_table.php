<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionItemsTable extends Migration
{
    public function up()
    {
        Schema::create('subscription_items', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->bigInteger('subscription_id')->unsigned();
            $table->string('stripe_id');
            $table->string('stripe_plan');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscription_items');
    }
}