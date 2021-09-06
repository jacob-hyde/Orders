<?php

namespace JacobHyde\Orders\App\Http\Controllers;

use JacobHyde\Orders\App\Http\Requests\CouponCodeUpdateRequest;
use JacobHyde\Orders\App\Http\Resources\CouponCodeResource;
use JacobHyde\Orders\Facades\Payment;
use JacobHyde\Orders\Models\CouponCode;
use JacobHyde\Orders\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CouponCodeController extends Controller
{
    public function store(CouponCodeUpdateRequest $request)
    {
        $data = $request->except(['product_type']);
        $product_type = ProductType::where('type', $request->product_type)->firstOrFail();
        $data['product_type_id'] = $product_type->id;
        $couponCode = CouponCode::create($data);
        $couponCode->save();

        return (new CouponCodeResource($couponCode))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function show($id)
    {
        $couponCode = CouponCode::withTrashed()->findOrFail($id);

        return (new CouponCodeResource($couponCode))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function update($id, CouponCodeUpdateRequest $request)
    {
        $couponCode = CouponCode::withTrashed()->findOrFail($id);

        $data = $request->except(['product_type']);
        $product_type = ProductType::where('type', $request->product_type)->firstOrFail();
        $data['product_type_id'] = $product_type->id;

        $couponCode->update($data);
        $couponCode->save();

        return (new CouponCodeResource($couponCode))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function changeStatus($id, Request $request)
    {
        $couponCode = CouponCode::withTrashed()->findOrFail($id);
        $data = $request->all();

        if (isset($data['active'])) {
            $couponCode->active = $data['active'];
        }
        if (isset($data['applies_to_vendor'])) {
            $couponCode->applies_to_vendor = $data['applies_to_vendor'];
        }
        if (isset($data['deleted'])) {
            $couponCode->deleted_at = $data['deleted'] ? date("Y-m-d H:i:s",time()) : null;
        }

        $couponCode->save();

        return (new CouponCodeResource($couponCode))
            ->response()
            ->setStatusCode(Response::HTTP_OK);

    }

    public function verify(Request $request)
    {
        $data = $request->all();

        $coupon_code = CouponCode::where('code', $data['code'])->first();

        if (!$coupon_code) {
            return $this->regularResponse(['message' => 'No Coupon Code is existing.'], false, null, Response::HTTP_INTERNAL_SERVER_ERROR);
        } else if (!$coupon_code->active) {
            return $this->regularResponse(['message' => 'Already Used.'], false, null, Response::HTTP_INTERNAL_SERVER_ERROR);
        } else if ($coupon_code->productType->type != $data['product_type']) {
            return $this->regularResponse(['message' => 'Current Coupon Code Product Type is not matched.'], false, null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $discount_price = config('arorders.coupon_calculations')[$coupon_code->productType->type];
        $discount_price = call_user_func($discount_price . '::calculateDiscountPrice', $coupon_code, $data);

        return $this->regularResponse(['id' => $coupon_code->id, 'price' => Payment::convertCentsToDollars($discount_price)]);
    }
}