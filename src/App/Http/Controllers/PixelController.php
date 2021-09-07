<?php

namespace KnotAShell\Orders\App\Http\Controllers;

use KnotAShell\Orders\App\Services\CAPIService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class PixelController extends Controller
{
    public function index(Request $request)
    {
        $event_id = time() . '.' . mt_rand(0, 100000);
        $user = null;
        if (config('orders.user')::resolveUser()) {
            $user = config('orders.user')::resolveUser();
            Cache::add($user->id . '.pixel.event', $event_id, 120);
        }

        if ($request->view) {
            $capi_service = new CAPIService();
            $capi_service->call($event_id, $request->ip(), $request->server('HTTP_USER_AGENT'), $request->headers->get('referer'), $user, $request->_fbp, $request->_fbc, CAPIService::EVENT_PAGE_VIEW);
            if ($user) {
                Cache::forget($user->id . '.pixel.event');
            }
        }

        return $this->regularResponse(['event_id' => $event_id]);
    }
}
