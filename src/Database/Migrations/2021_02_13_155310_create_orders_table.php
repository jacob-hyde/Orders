<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->unsignedInteger('api_client_id')->nullable();
            $table->unsignedInteger('product_type_id');
            $table->unsignedInteger('payment_id');
            $table->unsignedInteger('subscription_plan_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('buyer_user_id');
            $table->unsignedInteger('seller_user_id')->nullable();
            $table->enum('processor', ['stripe', 'paypal'])->default('stripe');
            $table->enum('status', ['pending', 'completed', 'refunded', 'partial-refund']);
            $table->integer('amount');
            $table->unsignedInteger('coupon_code_id')->nullable();
            $table->string('invoice_url')->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('product_type_id')->references('id')->on('product_types');
            $table->foreign('payment_id')->references('id')->on('payments');
            $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans');
            $table->foreign('subscription_id')->references('id')->on('subscriptions');
            $table->foreign('coupon_code_id')->references('id')->on('coupon_codes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
