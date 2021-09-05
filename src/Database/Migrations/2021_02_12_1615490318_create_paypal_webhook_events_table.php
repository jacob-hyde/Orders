<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaypalWebhookEventsTable extends Migration
{
    public function up()
    {
        Schema::create('paypal_webhook_events', function (Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->string('event_id');
			$table->datetime('event_time')->nullable();
			$table->string('resource_type');
			$table->string('event_type');
			$table->string('resource_id')->nullable();
			$table->text('summary');
            $table->longText('event');
			$table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypal_webhook_events');
    }
}