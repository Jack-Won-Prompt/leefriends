<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SupplierUnitPrice;
use App\Models\SupplyProduct;
use App\Models\SupplyProductUnit;
use Illuminate\Database\Seeder;

/**
 * 재료 발주 카탈로그(SupplyProduct) 구성 — 본사/공급사 배정 반영.
 * 대분류: 마카롱(MAC) · 쿠키(COO) · 재료(MAT)
 *
 * 공급사 배정 (사업자등록증 업태 기준):
 *  - 아이브릭스(I-BRIX)      : 마카롱 · 쿠키 (SNS마켓·전자상거래)
 *  - 주식회사 진영트레이딩    : 망고·파우더·차류 (mango@, 종합도매)
 *  - 우정푸드시스템          : 떡·팥·연유·브라우니 등 식품 (냉동식품·과실채소가공·식품제조)
 *
 * 단가 = 매장 출고가(store_price), 공급가 = 공급처→본사(supply_price).
 * 망고(MAT010)는 시세품목(40,000~65,000) → 출고 하한 40,000 / 공급 33,000.
 */
class OrderCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // 기존 카탈로그/단위/공급처단가 제거 (주문은 product_name 스냅샷으로 보존됨)
        SupplierUnitPrice::query()->delete();
        SupplyProductUnit::query()->delete();
        SupplyProduct::query()->delete();

        $sup = Supplier::pluck('id', 'name'); // name => id
        $IBRIX = '아이브릭스(I-BRIX)';
        $JY = '주식회사 진영트레이딩';
        $WJ = '우정푸드시스템';

        // [대분류, 품목코드, 품목명, 규격, 단위, 출고가, 공급사명, 공급가]
        $items = [
            ['마카롱', 'MAC001', '뚱카롱 돼지바', '10개입', 'BOX', 18500, $IBRIX, 15000],
            ['마카롱', 'MAC002', '뚱카롱 딸기', '10개입', 'BOX', 18500, $IBRIX, 15000],
            ['마카롱', 'MAC003', '뚱카롱 오레오', '10개입', 'BOX', 18500, $IBRIX, 15000],
            ['마카롱', 'MAC004', '뚱카롱 황치즈', '10개입', 'BOX', 18000, $IBRIX, 14500],
            ['마카롱', 'MAC005', '뚱카롱 바닐라', '10개입', 'BOX', 18000, $IBRIX, 14500],
            ['마카롱', 'MAC006', '뚱카롱 요거트', '10개입', 'BOX', 18000, $IBRIX, 14500],
            ['마카롱', 'MAC007', '뚱카롱 엄마는 외계인', '10개입', 'BOX', 18500, $IBRIX, 15000],
            ['마카롱', 'MAC008', '뚱카롱 바나나', '10개입', 'BOX', 18500, $IBRIX, 15000],
            ['마카롱', 'MAC009', '샌드마카롱 오레오', '20개입', 'BOX', 16000, $IBRIX, 13000],
            ['마카롱', 'MAC010', '샌드마카롱 딸기우유', '20개입', 'BOX', 16000, $IBRIX, 13000],
            ['마카롱', 'MAC011', '샌드마카롱 바닐라', '20개입', 'BOX', 16000, $IBRIX, 13000],
            ['마카롱', 'MAC012', '샌드마카롱 요거트', '20개입', 'BOX', 16000, $IBRIX, 13000],
            ['마카롱', 'MAC013', '샌드마카롱 황치즈', '20개입', 'BOX', 16000, $IBRIX, 13000],
            ['마카롱', 'MAC014', '샌드마카롱 우유', '20개입', 'BOX', 16000, $IBRIX, 13000],
            ['마카롱', 'MAC015', '샌드마카롱 블루베리', '20개입', 'BOX', 16000, $IBRIX, 13000],
            ['쿠키', 'COO001', '두바이 쫀득쿠키', '10개입', 'BOX', 2300, $IBRIX, 1800],
            ['쿠키', 'COO002', '아몬드 쫀득쿠키', '10개입', 'BOX', 2600, $IBRIX, 2000],
            ['재료', 'MAT001', '원액유', '1.68L', 'EA', 10000, $WJ, 8000],
            ['재료', 'MAT002', '큰떡', '2kg', 'EA', 21000, $WJ, 17000],
            ['재료', 'MAT003', '작은떡', '2kg', 'EA', 21000, $WJ, 17000],
            ['재료', 'MAT004', '팥', '3kg', 'EA', 32000, $WJ, 26000],
            ['재료', 'MAT005', '초코 브라우니', '2봉', 'EA', 28000, $WJ, 23000],
            ['재료', 'MAT006', '츄러스', '개', 'EA', 1000, $WJ, 800],
            ['재료', 'MAT007', '연유', '5kg', 'EA', 25000, $WJ, 20000],
            ['재료', 'MAT008', '콩가루', '600g', 'EA', 5500, $WJ, 4500],
            ['재료', 'MAT009', '아이스티', '1kg', 'EA', 12500, $JY, 10000],
            ['재료', 'MAT010', '망고', '5kg', 'EA', 40000, $JY, 33000], // 시세 40,000~65,000
            ['재료', 'MAT011', '요거트 파우더', '1kg', 'EA', 10000, $JY, 8000],
            ['재료', 'MAT012', '애플망고 파우더', '1kg', 'EA', 10000, $JY, 8000],
            ['재료', 'MAT013', '말차가루', '500g', 'EA', 6500, $JY, 5200],

            // 본사 직공급(hq) — 공급사 미지정 → supply_type='hq' (공급가 0)
            ['재료', 'MAT014', '리프렌즈 시그니처 시럽', '1L', 'EA', 9000, null, 0],
            ['재료', 'MAT015', '리프렌즈 전용 로고컵', '50개입', 'BOX', 12000, null, 0],
        ];

        $sort = 0;
        foreach ($items as [$category, $code, $name, $spec, $unit, $storePrice, $supName, $supplyPrice]) {
            $supplierId = $supName ? ($sup[$supName] ?? null) : null;
            $type = $supplierId ? 'supplier' : 'hq';

            $product = SupplyProduct::create([
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'category_code' => SupplyProduct::CATEGORY_CODES[$category] ?? null,
                'spec' => $spec,
                'unit' => $unit,
                'supply_type' => $type,
                'supplier_id' => $supplierId,
                'supply_price' => $type === 'supplier' ? $supplyPrice : 0,
                'store_price' => $storePrice,
                'registered_by' => 'hq',
                'approval_status' => 'approved',
                'sort_order' => ++$sort,
                'is_active' => true,
            ]);

            $defaultUnit = $product->units()->create([
                'name' => $unit,
                'store_price' => $storePrice,
                'supply_price' => $type === 'supplier' ? $supplyPrice : 0,
                'is_default' => true,
                'sort_order' => 0,
            ]);

            // 공급사 품목: 공급처×단위 공급가 등록
            if ($type === 'supplier') {
                $product->supplierPrices()->create([
                    'supplier_id' => $supplierId,
                    'supply_product_unit_id' => $defaultUnit->id,
                    'supply_price' => $supplyPrice,
                ]);
            }
        }
    }
}
