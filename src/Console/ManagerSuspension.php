<?php
namespace JacobHyde\Orders\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ManagerSuspension extends Command
{
    protected $signature = 'manager:suspend';

    protected $description = 'Suspend managers who have no completed their orders';

    public function handle()
    {
        $manager_accounts = config('manager-suspension');
        foreach ($manager_accounts as $order_class => $options) {
            $orders_table_name = with(new $order_class)->getTable();
            $query = $order_class::leftJoin($options['manager_table'].' as mt', 'mt.id', '=', $orders_table_name.'.'.$options['manager_id_column'])
                    ->where('mt.suspended', 0);
            
            if (Arr::has($options, 'change_value')) {
                $query->where($options['change'], Arr::get($options, 'change_operation', '='), Arr::get($options, 'change_value'));
            } elseif (Arr::has($options, 'change_raw')) {
                $query->whereRaw($options['change_raw']);
            } else {
                $query->whereNull($options['change']);
            }

            $orders = $query->whereRaw('HOUR(TIMEDIFF(NOW(), ' . $orders_table_name . '.created_at)) >= ' . $options['times'][0]['hours'][0])
                ->paid()
                ->get();
            
            if ($orders->count() === 0) {
                continue;
            }

            foreach ($options['times'] as $time) {
                $time_period_orders = collect();
                foreach ($orders as $i => $order) {
                    $working_hours_since_creation = $order->created_at->diffInHoursFiltered(function (Carbon $date) {
                        return !$date->isWeekend();
                    }, now());
                    
                    //check user subscription
                    $is_subscribed = false;
                    if(count($options['subscriptions']) > 0) {
                        foreach ($options['subscriptions'] as $subscription_name) {
                            $is_subscribed = $order->{$options['manager_relation']}->user->subscribed($subscription_name);
                            if ($is_subscribed) {
                                break;
                            }
                        }
                    }

                    if ($is_subscribed) {
                        $start_hr = $time['hours_subscription'][0];
                        $end_hr = $time['hours_subscription'][1];
                    } else {
                        $start_hr = $time['hours'][0];
                        $end_hr = $time['hours'][1];
                    }

                    if ($working_hours_since_creation >= $start_hr && $working_hours_since_creation < $end_hr) {
                        $time_period_orders->push($order);
                        unset($orders[$i]);
                    }
                }

                foreach ($time_period_orders->groupBy($options['manager_id_column']) as $time_period_order) {
                    if ($time_period_order->count() < $time['count']) {
                        continue;
                    }
                    $manager = $time_period_order->first()->{$options['manager_relation']};
                    if ($time['email']) {
                        Mail::to($manager->user->email)->queue(new $time['email']($manager));
                    }
                    if (isset($time['action'])) {
                        $time['action']($manager);
                    }
                    if ($time['suspend']) {
                        $manager->update([
                            'suspended' => 1,
                            'suspended_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}