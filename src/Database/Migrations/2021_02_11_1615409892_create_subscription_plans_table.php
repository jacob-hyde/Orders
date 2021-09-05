<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPlansTable extends Migration
{
    public function up()
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->string('name');
			$table->string('type');
            $table->unsignedInteger('product_type_id');
			$table->string('stripe_plan');
			$table->text('description');
			$table->integer('planable_id')->unsigned();
			$table->string('planable_type');
			$table->timestamps();
			$table->softDeletes();
            $table->foreign('product_type_id')->references('id')->on('product_types');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscription_plans');
    }
}