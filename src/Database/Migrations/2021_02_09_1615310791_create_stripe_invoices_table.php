<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStripeInvoicesTable extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_invoices', function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger('user_id');
            $table->string('invoice_id');
            $table->string('customer_id');
            $table->string('subscription_id');
            $table->integer('amount_due');
            $table->integer('amount_paid')->nullable();
            $table->string('status');
            $table->string('billing_reason');
            $table->string('invoice_pdf');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_invoices');
    }
}
