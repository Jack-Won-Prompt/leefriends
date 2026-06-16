<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Notice;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Admin account ----
        User::updateOrCreate(
            ['email' => 'admin@mangobing.kr'],
            [
                'name' => '관리자',
                'password' => Hash::make('admin1234'),
                'is_admin' => true,
            ]
        );

        // ---- Menus ----
        $menus = [
            ['signature', '망고치즈빙수', 'Mango Cheese Bingsu', '농익은 애플망고와 부드러운 크림치즈가 어우러진 시그니처 빙수', 15900, 'mango-cheese-bingsu', 'best', 1],
            ['signature', '애플망고빙수', 'Apple Mango Bingsu', '한 통 가득 올린 애플망고, 리프렌즈의 자존심', 16900, 'apple-mango-bingsu', 'best', 2],
            ['bingsu', '망고요거트빙수', 'Mango Yogurt Bingsu', '상큼한 요거트 빙수 위 생망고 토핑', 13900, 'mango-yogurt-bingsu', null, 3],
            ['bingsu', '트로피컬망고빙수', 'Tropical Mango Bingsu', '망고·파인애플·패션후르츠가 가득한 열대 빙수', 14900, 'tropical-mango-bingsu', 'new', 4],
            ['bingsu', '망고팥빙수', 'Mango Patbingsu', '국산 팥과 망고의 전통과 트렌드의 만남', 12900, 'mango-patbingsu', null, 5],
            ['bingsu', '망고초코빙수', 'Mango Choco Bingsu', '진한 벨기에 초코와 망고의 달콤한 조화', 13900, 'mango-choco-bingsu', null, 6],
            ['drink', '망고에이드', 'Mango Ade', '톡 쏘는 청량감과 생망고의 만남', 5900, 'mango-ade', null, 7],
            ['drink', '망고스무디', 'Mango Smoothie', '생망고를 통째로 갈아 만든 진한 스무디', 6900, 'mango-smoothie', 'best', 8],
            ['drink', '망고요거트스무디', 'Mango Yogurt Smoothie', '요거트와 망고의 부드러운 블렌딩', 7200, 'mango-yogurt-smoothie', null, 9],
            ['drink', '망고주스', 'Mango Juice', '100% 망고 과즙의 순수한 한 잔', 6200, 'mango-juice', null, 10],
            ['dessert', '망고크림케이크', 'Mango Cream Cake', '생크림과 생망고가 층층이 쌓인 케이크', 7900, 'mango-cream-cake', null, 11],
            ['dessert', '망고타르트', 'Mango Tart', '바삭한 타르트 위 향긋한 망고', 6900, 'mango-tart', 'new', 12],
            ['dessert', '망고푸딩', 'Mango Pudding', '입에서 녹는 부드러운 망고 푸딩', 5500, 'mango-pudding', null, 13],
        ];
        foreach ($menus as [$cat, $name, $en, $desc, $price, $img, $badge, $order]) {
            Menu::updateOrCreate(
                ['name' => $name],
                [
                    'category' => $cat,
                    'name_en' => $en,
                    'description' => $desc,
                    'price' => $price,
                    'image' => "images/menu/$img.svg",
                    'badge' => $badge,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }

        // ---- Stores ----
        $stores = [
            ['리프렌즈 강남본점', '서울', '서울특별시 강남구 테헤란로 123 망고빌딩 1층', '02-1234-5678', '매일 11:00 - 22:00', 37.4979, 127.0276],
            ['리프렌즈 홍대점', '서울', '서울특별시 마포구 양화로 45 2층', '02-2345-6789', '매일 11:00 - 23:00', 37.5563, 126.9220],
            ['리프렌즈 잠실롯데점', '서울', '서울특별시 송파구 올림픽로 300 롯데몰 B1', '02-3456-7890', '매일 10:30 - 22:00', 37.5133, 127.1028],
            ['리프렌즈 판교점', '경기', '경기도 성남시 분당구 판교역로 152 1층', '031-456-7890', '매일 11:00 - 22:00', 37.3947, 127.1113],
            ['리프렌즈 수원인계점', '경기', '경기도 수원시 팔달구 인계로 178 1층', '031-567-8901', '매일 11:00 - 22:00', 37.2730, 127.0289],
            ['리프렌즈 부산서면점', '부산', '부산광역시 부산진구 중앙대로 692 1층', '051-678-9012', '매일 11:00 - 23:00', 35.1577, 129.0594],
            ['리프렌즈 해운대점', '부산', '부산광역시 해운대구 구남로 24 1층', '051-789-0123', '매일 11:00 - 24:00', 35.1631, 129.1639],
            ['리프렌즈 대구동성로점', '대구', '대구광역시 중구 동성로2가 89 1층', '053-890-1234', '매일 11:00 - 22:30', 35.8693, 128.5957],
        ];
        foreach ($stores as [$name, $region, $addr, $phone, $hours, $lat, $lng]) {
            Store::updateOrCreate(
                ['name' => $name],
                [
                    'region' => $region,
                    'address' => $addr,
                    'phone' => $phone,
                    'hours' => $hours,
                    'lat' => $lat,
                    'lng' => $lng,
                    'image' => 'images/store/default.svg',
                    'is_active' => true,
                ]
            );
        }

        // ---- Notices ----
        $notices = [
            ['event', '🥭 여름 시즌 한정 «트로피컬 망고빙수» 출시!', "무더운 여름을 시원하게 책임질 트로피컬 망고빙수가 출시되었습니다.\n망고, 파인애플, 패션후르츠가 가득 담긴 신메뉴를 지금 만나보세요.\n\n· 기간: 2026년 6월 ~ 8월\n· 판매 매장: 전국 리프렌즈 매장", true, 1280],
            ['notice', '리프렌즈 멤버십 적립 혜택 안내', "리프렌즈 멤버십 회원이 되시면 결제 금액의 5%를 적립해 드립니다.\n적립된 포인트는 전 매장에서 현금처럼 사용 가능합니다.", true, 842],
            ['news', '리프렌즈, 2026 대한민국 디저트 브랜드 대상 수상', "리프렌즈가 2026 대한민국 디저트 브랜드 대상 빙수 부문에서 대상을 수상했습니다.\n앞으로도 최고 품질의 망고 디저트로 보답하겠습니다.", false, 631],
            ['notice', '여름철 매장 운영시간 변경 안내', "성수기를 맞아 일부 매장의 운영시간이 연장됩니다.\n자세한 사항은 매장 안내 페이지를 참고해 주세요.", false, 410],
            ['event', '신규 가맹점 오픈 «수원인계점» 오픈 이벤트', "수원인계점 오픈을 기념하여 망고스무디 1+1 이벤트를 진행합니다.\n많은 관심 부탁드립니다.", false, 298],
        ];
        foreach ($notices as [$cat, $title, $content, $pinned, $views]) {
            Notice::updateOrCreate(
                ['title' => $title],
                [
                    'category' => $cat,
                    'content' => $content,
                    'is_pinned' => $pinned,
                    'views' => $views,
                    'published_at' => now(),
                ]
            );
        }

        // B2B 발주 모듈 데이터 (공급처/재료/역할별 계정)
        $this->call(B2bSeeder::class);

        // 재료 발주 카탈로그 (마카롱/쿠키/재료)
        $this->call(OrderCatalogSeeder::class);
    }
}
