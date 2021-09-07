<?php

namespace KnotAShell\Orders\App\Http\Controllers;

use KnotAShell\Orders\App\Http\Requests\PaymentUpdateRequest;
use KnotAShell\Orders\App\Http\Resources\PaymentResource;
use KnotAShell\Orders\App\Services\CAPIService;
use KnotAShell\Orders\Facades\Payment;
use KnotAShell\Orders\Models\Payment as PaymentModel;
use KnotAShell\Orders\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    public function update(PaymentModel $payment, PaymentUpdateRequest $request)
    {
        if ($request->recurring) {
            Payment::createSubscription($payment, $request->all());
        } else {
            Payment::update($payment, $request->except(['recurring', '_fbp', '_fbc']));
        }

        if ($payment->status === PaymentModel::STATUS_PAID) {
            $capi_service = new CAPIService();
            $capi_service->call(null, $request->ip(), $request->server('HTTP_USER_AGENT'), $request->headers->get('referer'), config('orders.user')::resolveUser(), $request->_fbp, $request->_fbc, CAPIService::EVENT_PURCHASE, 'USD', 1, $payment->order);
        }

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function refund(Payment $payment, Request $request)
    {
        $amount = $request->query('amount', null);
        Payment::refund($payment, $amount);

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function payout()
    {
        $user = config('orders.user')::resolveUser();
        if (!$user) {
            return $this->regularResponse([], false, 'USER_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        $error = null;
        $model = $user->{config('orders.payout_model_relationship')};
        $amount = $model->payout_amount;
        $error = call_user_func(config('orders.payout_rules') . '::checkPayoutRules', $user, $amount);
        $email_subject = config('orders.payout_email_subject');
        $note = config('orders.payout_note');

        if ($error) {
            return $this->regularResponse([], false, $error[0], 200, $error[1]);
        }

        $model->paid_out_amount = $model->paid_out_amount + $amount;
        $model->last_payout = now();
        $model->save();
        Payment::payout($user, Payment::convertCentsToDollars($amount), $email_subject, $note);

        return $this->regularResponse([]);
    }

    public function cancelSubscription(Request $request)
    {
        $user = config('orders.user')::resolveUser();

        if (!$user) {
            return $this->regularResponse([], false, 'USER_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        if (!$subscription_plan_id = $request->query('subscription_plan_id')) {
            return $this->regularResponse([], false, 'ERR_NO_TYPE', 400);
        }
        $subscription_plan = SubscriptionPlan::find($subscription_plan_id);

        $res = Payment::cancelSubscription($user, $subscription_plan->type, $subscription_plan->stripe_plan);

        return response($res)
            ->setStatusCode(Response::HTTP_OK);
    }
}
