<?php

namespace App\Market\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MarketScope(상권분석) 모듈 공통 베이스 모델.
 * 모든 market 테이블은 같은 서버의 별도 `market` DB 커넥션을 사용한다.
 */
abstract class BaseModel extends Model
{
    protected $connection = 'market';
}
