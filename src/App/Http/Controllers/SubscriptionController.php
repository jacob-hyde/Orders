<?php

namespace JacobHyde\Orders\App\Http\Controllers;

use JacobHyde\Orders\App\Http\Controllers\Controller;
use JacobHyde\Orders\App\Http\Resources\SubscriptionResource;
use JacobHyde\Orders\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = config('arorders.user')::resolveUser();
        if (!$user) {
            return $this->regularResponse([], false, 'USER_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }
        $subscriptions = Subscription::withBillsNext()
            ->where('user_id', $user->id)
            ->active()
            ->with(['subscription_plan'])
            ->get();
        
        return SubscriptionResource::collection($subscriptions)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['orders']);

        return (new SubscriptionResource($subscription))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function destroy(Subscription $subscription)
    {
        $user = config('arorders.user')::resolveUser();
        if (!$user) {
            return $this->regularResponse([], false, 'USER_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }
        $user->subscription($subscription->name)->cancel();
        return $this->regularResponse();
    }
}