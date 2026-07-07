<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // 네이버 콘텐츠 (welcome 페이지 블로그 자동수집)
    'naver' => [
        'blog_id' => env('NAVER_BLOG_ID', 'mangojung_official'),  // 공식 네이버 블로그 아이디
    ],

    // 본사(회사) 정보
    'company' => [
        'email' => env('COMPANY_EMAIL'),  // 본사 수신 이메일
    ],

    // FCM (Firebase Cloud Messaging) - 모바일 앱 푸시
    'fcm' => [
        'credentials' => env('FCM_CREDENTIALS'),        // 서비스계정 JSON 경로 (예: storage/app/firebase/sa.json)
        'project_id' => env('FCM_PROJECT_ID'),          // 미지정 시 JSON 의 project_id 사용
    ],

    // 세금계산서 발행 드라이버: internal(내부) | popbill(추후 전자세금계산서 연동)
    'tax_invoice' => [
        'driver' => env('TAX_INVOICE_DRIVER', 'internal'),
        'popbill' => [
            'link_id' => env('POPBILL_LINK_ID'),
            'secret_key' => env('POPBILL_SECRET_KEY'),
            'corp_num' => env('POPBILL_CORP_NUM'),    // 본사(공급받는자) 사업자번호
            'is_test' => env('POPBILL_IS_TEST', true),
        ],
    ],

];
