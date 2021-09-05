<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->integer('user_id')->unsigned()->nullable();
			$table->string('first_name')->nullable();
			$table->string('last_name')->nullable();
			$table->string('email')->nullable();
			$table->string('phone')->nullable();
			$table->string('address')->nullable();
			$table->string('secondary_address')->nullable();
			$table->string('city')->nullable();
			$table->string('province')->nullable();
			$table->string('zip')->nullable();
			$table->string('country')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
}