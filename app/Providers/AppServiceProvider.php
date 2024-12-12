<?php

namespace App\Providers;

use App\Services\CODPaymentService;
use App\Services\PaymentServiceInterface;
use Illuminate\Support\ServiceProvider;
use App\Services\PayOSService;
use App\Services\PayOSServiceInterface;
use App\Services\VnpayService;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Đăng ký binding theo phương thức thanh toán
        $this->app->bind(PaymentServiceInterface::class, function ($app) {
            $paymentMethodId = request()->input('payment_id');

            switch ($paymentMethodId) {
                    case 2:
                        return app(PayOSService::class);
                    case 3:
                        return app(VnpayService::class);
                    default:
                        return app(CODPaymentService::class);
                }
        });
        $this->app->singleton(PayOSServiceInterface::class, function ($app) {
            return new PayOSService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
