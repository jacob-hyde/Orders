<?php

namespace KnotAShell\Orders\App\Jobs\Paypal;

use KnotAShell\Orders\Models\PaypalPayout;
use KnotAShell\Orders\Models\PaypalWebhookEvent;
use Carbon\Carbon;
use \Spatie\WebhookClient\ProcessWebhookJob as SpatieProcessWebhookJob;

class PaypalWebhook extends SpatieProcessWebhookJob
{
    public $data;
    public $paypal_webhook_event;

    public function handle()
    {
        $this->data = $this->webhookCall->payload;
        $this->paypal_webhook_event = PaypalWebhookEvent::create([
            'event_id' => $this->data['id'],
            'event_time' => Carbon::parse($this->data['create_time']),
            'resource_type' => $this->data['resource_type'],
            'event_type' => $this->data['event_type'],
            'summary' => $this->data['summary'],
            'event' => json_encode($this->data),
        ]);

        if ($this->data['resource_type'] === 'payouts_item') {
            $payout = PaypalPayout::where('payout_batch_id', $this->data['resource']['payout_batch_id'])->first();
            if (!$payout) {
                $this->paypal_webhook_event->delete();
                return;
            }
            $payout->status = $this->data['resource']['transaction_status'];
            $payout->save();
            $this->paypal_webhook_event->resource_id = $this->data['resource']['payout_batch_id'];
            $this->paypal_webhook_event->save();
        }
    }
}