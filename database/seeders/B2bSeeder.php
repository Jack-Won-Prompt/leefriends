<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class B2bSeeder extends Seeder
{
    public function run(): void
    {
        // ---- 공급처 (사업자등록증 기준) ----
        // [name, biz_no, ceo, phone, email, address, address_detail]
        $suppliers = [
            ['아이브릭스(I-BRIX)', '451-70-00633', '박미정', '', 'I-brixair@naver.com', '인천광역시 중구 공항동로296번길 98-11', 'B동 2층 223호(운서동)'],
            ['주식회사 진영트레이딩', '416-86-03550', '양예진', '070-4705-0650', 'mango@jytrading.biz', '경기도 남양주시 진접읍 팔야로 133', ''],
            ['우정푸드시스템', '127-03-88597', '양교석', '', 'woojungfood@hanmail.net', '경기도 포천시 소흘읍 광릉수목원로 826-16', ''],
        ];
        $supplierModels = [];
        foreach ($suppliers as [$name, $biz, $ceo, $phone, $email, $addr, $addrDetail]) {
            $supplierModels[$name] = Supplier::updateOrCreate(
                ['biz_no' => $biz],
                ['name' => $name, 'ceo' => $ceo, 'phone' => $phone, 'email' => $email,
                    'address' => $addr, 'address_detail' => $addrDetail, 'is_active' => true]
            );
        }

        // 발주 품목(SupplyProduct)은 OrderCatalogSeeder에서 구성됨.

        // ---- 재료 마스터 데모 (추가 품목 재료 / 기타 재료) ----
        $materials = [
            ['extra', '망고 시럽 펌프', '부자재', '개', '1L', 1],
            ['extra', '빙수 스푼', '소모품', '봉', '100입', 2],
            ['etc', '주방 세제', '청소', '통', '4L', 1],
            ['etc', '위생장갑', '소모품', '박스', '200매', 2],
        ];
        foreach ($materials as [$type, $name, $cat, $unit, $spec, $sort]) {
            Material::updateOrCreate(
                ['name' => $name],
                ['type' => $type, 'category' => $cat, 'unit' => $unit, 'spec' => $spec, 'sort_order' => $sort, 'is_active' => true]
            );
        }

        // ---- 역할별 데모 계정 ----
        // 본사 (오다넷시스템)
        User::updateOrCreate(
            ['email' => 'hq@leefriends.kr'],
            ['name' => '오다넷시스템', 'password' => Hash::make('1234'), 'is_admin' => true, 'role' => 'hq']
        );
        // 기존 마케팅 관리자도 본사 역할 부여
        User::where('email', 'admin@mangobing.kr')->update(['role' => 'hq']);

        // 매장 (첫 번째 매장에 연결)
        $store = Store::orderBy('id')->first();
        if ($store) {
            User::updateOrCreate(
                ['email' => 'store@leefriends.kr'],
                ['name' => $store->name . ' 점주', 'password' => Hash::make('1234'), 'role' => 'store', 'store_id' => $store->id]
            );
        }

        // 공급처 데모 계정 (아이브릭스 = 통합 데모 계정)
        User::updateOrCreate(
            ['email' => 'supplier@leefriends.kr'],
            ['name' => '아이브릭스 담당자', 'password' => Hash::make('1234'), 'role' => 'supplier',
                'supplier_id' => $supplierModels['아이브릭스(I-BRIX)']->id]
        );

        // 각 공급처별 로그인 계정 (사업자등록증 이메일 = 로그인 ID, 비밀번호 1234)
        foreach ($supplierModels as $name => $supplier) {
            if (! $supplier->email) {
                continue;
            }
            User::updateOrCreate(
                ['email' => $supplier->email],
                ['name' => $name . ' 담당자', 'password' => Hash::make('1234'), 'role' => 'supplier', 'supplier_id' => $supplier->id]
            );
        }
    }
}
