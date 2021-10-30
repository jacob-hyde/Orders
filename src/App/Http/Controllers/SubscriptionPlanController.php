<?php

namespace KnotAShell\Orders\App\Http\Controllers;

use KnotAShell\Orders\App\Http\Controllers\Controller;
use KnotAShell\Orders\App\Http\Requests\SubscriptionPlanRequest;
use KnotAShell\Orders\App\Http\Resources\PaymentResource;
use KnotAShell\Orders\App\Http\Resources\SubscriptionPlanResource;
use KnotAShell\Orders\Facades\Payment;
use KnotAShell\Orders\Models\Customer;
use KnotAShell\Orders\Models\Order;
use KnotAShell\Orders\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request)
    {
        if (!$type = $request->query('type')) {
            return response();
        }
        $subscription_plans = SubscriptionPlan::where('type', $type)->with(['planable'])->get();
        return SubscriptionPlanResource::collection($subscription_plans)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function store(SubscriptionPlanRequest $request)
    {
        $user = config('orders.user')::resolveUser();
        if (!$user) {
            return $this->regularResponse([], false, 'USER_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        $subscription_plan = SubscriptionPlan::findOrFail($request->plan_id);
        $payment = Payment::setUser($user)
            ->setRecurring(true)
            ->setSubscriptionPlan($subscription_plan)
            ->rememberCard($request->remember_card ? true : false)
            ->create();
        $customer = Customer::customerFromUser($user);
        Order::createOrder($payment, $customer, $user, Order::STATUS_PENDING, ($payment->amount + $payment->fee), $subscription_plan->product_type, null, auth('api')->user()->id, $subscription_plan);
        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}