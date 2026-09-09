<?php

namespace App\Providers;

use App\Market\Services\OpenData\PublicDataClient;
use App\Market\Services\OpenData\Sbiz\StoreCollector;
use App\Market\Services\OpenData\Seoul\SeoulOpenApiClient;
use App\Market\Services\Reports\StaticMapRenderer;
use Illuminate\Support\ServiceProvider;

/**
 * MarketScope(상권분석) 모듈 서비스 바인딩.
 * 오픈 API 클라이언트/정적지도 렌더러는 생성자 인자를 설정에서 읽어 만든다.
 */
class MarketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PublicDataClient::class, fn () => PublicDataClient::fromConfig());
        $this->app->singleton(SeoulOpenApiClient::class, fn () => SeoulOpenApiClient::fromConfig());
        $this->app->singleton(StoreCollector::class, fn () => StoreCollector::fromConfig());
        $this->app->singleton(StaticMapRenderer::class, fn () => StaticMapRenderer::fromConfig());
    }
}
