<?php

namespace JacobHyde\Orders\App\Http\Controllers;

use JacobHyde\Orders\App\Http\Controllers\Controller;
use JacobHyde\Orders\App\Http\Requests\SubscriptionPlanRequest;
use JacobHyde\Orders\App\Http\Resources\PaymentResource;
use JacobHyde\Orders\App\Http\Resources\SubscriptionPlanResource;
use JacobHyde\Orders\Facades\ARPayment;
use JacobHyde\Orders\Models\Customer;
use JacobHyde\Orders\Models\Order;
use JacobHyde\Orders\Models\SubscriptionPlan;
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
        $user = config('arorders.user')::resolveUser();
        if (!$user) {
            return $this->regularResponse([], false, 'USER_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        $subscription_plan = SubscriptionPlan::findOrFail($request->plan_id);
        $payment = ARPayment::setUser($user)
            ->setRecurring(true)
            ->setSubscriptionPlan($subscription_plan)
            ->rememberCard($request->remember_card ? true : false)
            ->create();
        $customer = Customer::customerFromUser($user);
        Order::createOrder($payment, $customer, $user, Order::STATUS_PENDING, ($payment->amount + $payment->fee), $subscription_plan->product_type, null, auth()->user()->id, $subscription_plan);
        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}