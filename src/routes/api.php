<?php

use JacobHyde\Orders\App\Http\Controllers\CouponCodeController;
use JacobHyde\Orders\App\Http\Controllers\PaymentController;
use JacobHyde\Orders\App\Http\Controllers\PixelController;
use JacobHyde\Orders\App\Http\Controllers\SubscriptionController;
use JacobHyde\Orders\App\Http\Controllers\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'payment'], function() {
    Route::put('{payment}', [PaymentController::class, 'update'])->name('payment.update');
    Route::get('{payment}/refund', [PaymentController::class, 'refund'])->name('payment.refund');
    Route::post('payout', [PaymentController::class, 'payout'])->name('payment.payout');
});
Route::group(['prefix' => 'subscription'], function () {
    Route::group(['prefix' => 'plan'], function () {
        Route::get('/', [SubscriptionPlanController::class, 'index'])->name('subscription.plan.index');
        Route::post('/', [SubscriptionPlanController::class, 'store'])->name('subscription.plan.create');
    });
    Route::get('/', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('{subscription}', [SubscriptionController::class, 'show'])->name('subscription.show');
    Route::delete('{subscription}', [SubscriptionController::class, 'destroy'])->name('subscription.cancel');
});

Route::group(['prefix' => 'admin', 'middleware' => ['admin']], function () {
    Route::group(['prefix' => 'coupon'], function () {
        Route::get('code-available', [CouponCodeController::class, 'codeAvailable'])->name('admin.coupon.codeAvailable');
        Route::post('/', [CouponCodeController::class, 'store'])->name('admin.coupon.store');
        Route::get('{coupon_code}', [CouponCodeController::class, 'show'])->name('admin.coupon.show');
        Route::put('{coupon_code}', [CouponCodeController::class, 'update'])->name('admin.coupon.update');
        Route::put('change-status/{coupon_code}', [CouponCodeController::class, 'changeStatus'])->name('admin.coupon.changeStatus');
    });
});

Route::get('/coupon/verify', [CouponCodeController::class, 'verify'])->name('coupon.verify');

Route::get('pixel-event', [PixelController::class, 'index']);
