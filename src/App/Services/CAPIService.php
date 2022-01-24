<?php

namespace JacobHyde\Orders\App\Services;

use JacobHyde\Orders\Payment;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CAPIService extends GuzzleRequestService
{

    public const EVENT_PURCHASE = 'Purchase';
    public const EVENT_PAGE_VIEW = 'PageView';
    public const EVENT_REGISTER = 'CompleteRegistration';

    private $client;

    public function __construct()
    {
        return;
        if (App::environment() !== 'production') {
            return;
        }
        $this->client = $this->_createClient(
            config('orders.capi.lambda_api'),
            ['x-api-key' => config('orders.capi.lambda_key')]
        );
    }

    public function call(?string $event_id, string $ip, string $user_agent, ?string $referer, $user, $fbp, $fbc, string $event_type = self::EVENT_PURCHASE, string $currency = 'usd', int $quantity = 1, $order = null)
    {
        return;
        if (App::environment() !== 'production') {
            return;
        }
        if (!$event_id && $user) {
            $event_id = Cache::get($user->id . '.pixel.event');
            if ($event_id) {
                Cache::forget($user->id . '.pixel.event');
            } else {
                $event_id = time() . '.' . mt_rand(0, 100000);
            }
        } else if (!$event_id && !$user) {
            return false;
        }

        $data = [
            'event_id' => $event_id,
            'ip' => $ip,
            'user_agent' => $user_agent,
            'referer' => $referer ? $referer : "",
            'user_id' => $user ? $user->external_user_id : "",
            'fbp' => $fbp ? $fbp : "",
            'fbc' => $fbc ? $fbc : "",
            'type' => $event_type,
            'currency' => $currency ? $currency : "",
            'quantity' => $quantity ? $quantity : ""
        ];

        if ($event_type === self::EVENT_PURCHASE && $order) {
            $data['order_uuid'] = $order->uuid;
            $data['price'] = (float) Payment::convertCentsToDollars($order->amount);
        }
        try {
            return $this->_doRequest($this->client, '', 'POST', [], $data);
        } catch (Exception $err) {
            Log::error($err);
        }
    }
}
