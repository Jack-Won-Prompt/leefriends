<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

/**
 * 망고정 실메뉴 카탈로그 (실제 제품 사진 public/images/menu/*.jpg).
 * name 기준 upsert 로 멱등하게 동작한다.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // [분류, 메뉴명, 가격, 이미지 슬러그(.jpg), 뱃지]
        $menus = [
            ['signature', '리얼 망고 눈꽃 빙수 (생과일)',            17900, 'real-mango-bingsu',         'best'],
            ['signature', '대만 애플 망고 눈꽃 빙수',                22900, 'taiwan-apple-mango-bingsu', 'hot'],
            ['signature', '양즈깐루 눈꽃 빙수 (생망고)',             19900, 'yangzhi-mango-bingsu',      null],
            ['bingsu',    '달콤한 메론 눈꽃빙수 (생과일)',           15900, 'melon-bingsu',              null],
            ['bingsu',    '시원한 수박 눈꽃빙수 (생과일)',           15900, 'watermelon-bingsu',         'new'],
            ['bingsu',    '전통가마솥 팥 인절미 눈꽃빙수',           13900, 'injeolmi-pat-bingsu',       null],
            ['bingsu',    '전통가마솥 팥 순우유 눈꽃빙수',           12900, 'milk-pat-bingsu',           null],
            ['bingsu',    '제주 말차 인절미 팥 눈꽃빙수',            13900, 'matcha-injeolmi-bingsu',    null],
            ['bingsu',    '고소한 인절미 눈꽃빙수 (팥 X)',           11900, 'injeolmi-bingsu',           null],
            ['dessert',   '오레오 & 아이스크림 눈꽃빙수',            13900, 'oreo-icecream-bingsu',      null],
            ['dessert',   '초코 나라 눈꽃 빙수',                    12900, 'choco-bingsu',              null],
            ['dessert',   '바삭 돼지바 & 바닐라아이스크림 눈꽃빙수', 12900, 'dwaejiba-bingsu',           null],
            ['bingsu',    '순수 우유 큐브 치즈 눈꽃빙수',            10900, 'cube-cheese-bingsu',        null],
            ['bingsu',    '순수 우유 눈꽃빙수',                     9900,  'milk-bingsu',               null],
        ];

        foreach ($menus as $i => [$cat, $name, $price, $img, $badge]) {
            Menu::updateOrCreate(
                ['name' => $name],
                [
                    'category' => $cat,
                    'name_en' => null,
                    'description' => null,
                    'price' => $price,
                    'image' => "images/menu/$img.jpg",
                    'badge' => $badge,
                    'sort_order' => $i + 1,
                    'is_active' => true,
                ]
            );
        }

        // 옛 플레이스홀더 메뉴(SVG 이미지) 정리 — 실메뉴는 전부 .jpg 사용
        Menu::where('image', 'like', '%.svg')->delete();
    }
}
