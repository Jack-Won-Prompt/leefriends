<?php

namespace App\Providers;

use App\Services\TaxInvoice\InternalIssuer;
use App\Services\TaxInvoice\PopbillIssuer;
use App\Services\TaxInvoice\TaxInvoiceIssuer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        // DomPDF 폰트 캐시(.ufm) 디렉터리를 웹 프로세스가 생성 → 쓰기권한 보장(프로덕션 권한오류 방지)
        $fontCache = storage_path('app/dompdf');
        if (! is_dir($fontCache)) {
            File::ensureDirectoryExists($fontCache, 0775);
        }

        // 모든 URL 생성을 APP_URL 기준으로 고정 — 초대 링크 등이 접속 호스트/프록시에
        // 영향받지 않고 항상 APP_URL 도메인·경로로 생성되도록 한다.
        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);
            if (Str::startsWith($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
