<?php

namespace JacobHyde\Orders\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    use HasFactory;

    public function subscription_plan()
    {
        return $this->hasOne(SubscriptionPlan::class, 'stripe_plan', 'stripe_plan');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function owner()
    {
        $model = config('cashier.model');
        return $this->belongsTo($model, (new $model)->getForeignKey())->withTrashed();
    }

    public function scopeWithBillsNext($query)
    {
        return $query->select($query->getQuery()->columns ?? '*')
            ->addSelect(DB::raw('(SELECT DATE_ADD(orders.created_at, INTERVAL 1 MONTH) FROM orders WHERE
                orders.subscription_id = subscriptions.id ORDER BY orders.id DESC LIMIT 1) as bills_next'));
    }

    public function getBillsNextAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value);
        }
        $last_order = Order::where('subscription_id', $this->id)->latest()->first();

        return $last_order->created_at->addMonth();
    }

    public function getDaysForCurrentPeriod()
    {
        $sub = $this->asStripeSubscription();
        $start = Carbon::createFromTimeStamp($sub->current_period_start);
        $end = Carbon::createFromTimestamp($sub->current_period_end);
        return $start->diffInDays($end);
    }

    public function getDaysTillEndOfPeriod()
    {
        $sub = $this->asStripeSubscription();
        $end = Carbon::createFromTimestamp($sub->current_period_end);
        return now()->diffInDays($end);
    }

    public function getSubscriptionMonthNumber()
    {
        $sub = $this->asStripeSubscription();
        $start = Carbon::createFromTimeStamp($sub->start_date);
        $month_diff = $start->diffInMonths(now());
        $month_number = $month_diff + 1;
        return $month_number;
    }


}
