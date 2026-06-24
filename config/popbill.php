<?php

return [
    'LinkID'            => env('POPBILL_ID'),
    'SecretKey'         => env('POPBILL_SECRET_KEY'),
    'IsTest'            => env('POPBILL_IS_TEST', true),
    'IPRestrictOnOff'   => env('POPBILL_IP_RESTRICT_ON_OFF', true),
    'UseStaticIP'       => env('POPBILL_USE_STATIC_IP', false),
    'UseLocalTimeYN'    => env('POPBILL_USE_LOCAL_TIME_YN', true),
    'LINKHUB_COMM_MODE' => env('POPBILL_LINKHUB_COMM_MODE', 'CURL'),

    // 테스트 발행 시 발행자(본사) 기본값 — 팝빌 테스트 환경에 등록된 테스트법인
    'test' => [
        'corp_num'  => env('POPBILL_TEST_CORP_NUM'),
        'user_id'   => env('POPBILL_TEST_USER_ID'),
        'cert_key'  => env('POPBILL_TEST_CERT_KEY'),
    ],

    // 본사(발행자=공급자) 사업자 정보 — 본사→매장 발행 시 사용
    'hq' => [
        'corp_num'  => env('POPBILL_HQ_CORP_NUM', '8278103115'),       // 주식회사 오다네트웍스 827-81-03115
        'corp_name' => env('POPBILL_HQ_CORP_NAME', '주식회사 오다네트웍스'),
        'ceo_name'  => env('POPBILL_HQ_CEO_NAME', '이윤석'),
        'addr'      => env('POPBILL_HQ_ADDR', '경기도 의정부시 천보로 14, 1113호(민락동)'),
        'biz_type'  => env('POPBILL_HQ_BIZ_TYPE', '도매 및 소매업'),
        'biz_class' => env('POPBILL_HQ_BIZ_CLASS', '전자상거래 소매 중개업'),
        'tel'       => env('POPBILL_HQ_TEL', ''),
        'email'     => env('POPBILL_HQ_EMAIL', env('COMPANY_EMAIL', '')),
    ],
];
