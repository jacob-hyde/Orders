<?php

namespace KnotAShell\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class StripeInvoice extends Model
{
    protected $table = 'stripe_invoices';

    protected $fillable = [
        'id',
        'user_id',
        'invoice_id',
        'customer_id',
        'subscription_id',
        'amount_due',
        'amount_paid',
        'status',
        'billing_reason',
        'invoice_pdf',
    ];

    public function user()
    {
        return $this->belongsTo(config('orders.user'));
    }
}
