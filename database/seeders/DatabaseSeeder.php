<?php

namespace Database\Seeders;

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

        // ---- Menus (망고정 실메뉴 카탈로그) ----
        $this->call(MenuSeeder::class);

        // ---- 과일 보관 가이드라인 (ZIM) ----
        $this->call(FruitStorageSeeder::class);

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
            ['news', '리프렌즈, 애플망고 농가 직거래 확대', "리프렌즈는 더 좋은 원물을 안정적으로 공급하기 위해 애플망고 직거래 농가를 확대하고 있습니다.\n신선한 제철 망고로 만든 디저트로 보답하겠습니다.", false, 631],
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
