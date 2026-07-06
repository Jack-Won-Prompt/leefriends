<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 매장 포털 로그인 계정(이메일·비밀번호) 업데이트.
 * 매장명 기준으로 Store 이메일과 연결 점주 계정을 upsert 한다 (멱등).
 */
class StoreCredentialSeeder extends Seeder
{
    public function run(): void
    {
        // 매장명 => [이메일, 비밀번호]
        $accounts = [
            '망고정 월계점' => ['email' => 'sun2red@naver.com', 'password' => '1234'],
        ];

        foreach ($accounts as $storeName => $cred) {
            $store = Store::where('name', $storeName)->first();
            if (! $store) {
                $this->command?->warn("매장을 찾을 수 없습니다: {$storeName}");
                continue;
            }

            // 매장 이메일 갱신 (거래명세서·알림 수신용)
            $store->update(['email' => $cred['email']]);

            // 매장 포털 점주 계정 upsert (role=store)
            User::updateOrCreate(
                ['store_id' => $store->id, 'role' => 'store'],
                [
                    'name' => $store->name.' 점주',
                    'email' => $cred['email'],
                    'password' => Hash::make($cred['password']),
                ]
            );

            $this->command?->info("계정 갱신: {$storeName} ({$cred['email']})");
        }
    }
}
