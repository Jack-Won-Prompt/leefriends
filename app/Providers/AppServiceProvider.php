<?php

namespace App\Providers;

use App\Services\TaxInvoice\InternalIssuer;
use App\Services\TaxInvoice\PopbillIssuer;
use App\Services\TaxInvoice\TaxInvoiceIssuer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 세금계산서 발행 드라이버 바인딩 (internal → 추후 popbill 전환)
        $this->app->bind(TaxInvoiceIssuer::class, function ($app) {
            return $app['config']->get('services.tax_invoice.driver') === 'popbill'
                ? new PopbillIssuer()
                : new InternalIssuer();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
