<?php
// 매장·본사·공급처 3자 거래 관계 정합성 검증 (PART A·B 읽기전용, PART C 롤백 시뮬레이션)
// 실행: php scripts/relation_check.php
error_reporting(E_ALL & ~E_DEPRECATED);
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupplyProduct;
use App\Services\Fulfillment\SalesOrderGenerator;
use Illuminate\Support\Facades\DB;

$issues = [];
$rec = fn (string $sev, string $code, string $msg) => $issues[] = compact('sev', 'code', 'msg');

echo "==================== PART A. 마스터 3자 관계 ====================\n";
// A1. 공급유형 ↔ supplier_id
$badSupplier = DB::table('supply_products')->where('supply_type', 'supplier')->whereNull('supplier_id')->pluck('id')->all();
foreach ($badSupplier as $id) $rec('ERROR', 'A1', "supply_product#$id: supplier 유형인데 supplier_id 없음");
$badHq = DB::table('supply_products')->where('supply_type', 'hq')->whereNotNull('supplier_id')->pluck('id')->all();
foreach ($badHq as $id) $rec('WARN', 'A1', "supply_product#$id: hq 유형인데 supplier_id 존재");

// A2. supplier_id 가 실재하는 공급처인지 + 활성 여부
foreach (DB::table('supply_products')->whereNotNull('supplier_id')->get() as $p) {
    $sup = DB::table('suppliers')->where('id', $p->supplier_id)->first();
    if (!$sup) $rec('ERROR', 'A2', "supply_product#{$p->id}: supplier_id={$p->supplier_id} 공급처 없음");
    elseif (!$sup->is_active) $rec('WARN', 'A2', "supply_product#{$p->id}: 비활성 공급처(#{$p->supplier_id}) 참조");
}

// A3. supplier_unit_prices ↔ product.supplier_id 일치, 단위 소속 일치
foreach (DB::table('supplier_unit_prices')->get() as $sup) {
    $p = DB::table('supply_products')->where('id', $sup->supply_product_id)->first();
    if (!$p) { $rec('ERROR', 'A3', "supplier_unit_price#{$sup->id}: 제품 없음"); continue; }
    if ((int) $sup->supplier_id !== (int) $p->supplier_id)
        $rec('ERROR', 'A3', "supplier_unit_price#{$sup->id}: 공급처({$sup->supplier_id}) ≠ 제품 기본공급처({$p->supplier_id})");
    if ($sup->supply_product_unit_id) {
        $u = DB::table('supply_product_units')->where('id', $sup->supply_product_unit_id)->first();
        if (!$u || (int) $u->supply_product_id !== (int) $sup->supply_product_id)
            $rec('ERROR', 'A3', "supplier_unit_price#{$sup->id}: 단위가 다른 제품 소속");
    }
}

// A4. 마진(매장가 ≥ 공급가) — 제품·단위 레벨
foreach (DB::table('supply_products')->where('supply_type', 'supplier')->get() as $p) {
    if ((int) $p->store_price < (int) $p->supply_price)
        $rec('WARN', 'A4', "supply_product#{$p->id}: 매장가({$p->store_price}) < 공급가({$p->supply_price}) 역마진");
}
foreach (DB::table('supply_product_units')->get() as $u) {
    if ((int) $u->store_price < (int) $u->supply_price)
        $rec('WARN', 'A4', "supply_product_unit#{$u->id}: 매장가({$u->store_price}) < 공급가({$u->supply_price}) 역마진");
}

echo "==================== PART B. 거래 관계 (기존 데이터) ====================\n";
$orderItems = DB::table('order_items')->get();
if ($orderItems->isEmpty()) {
    echo "  (거래 데이터 없음 — order_items 0건. PART C 시뮬레이션으로 로직 검증)\n";
}
foreach ($orderItems as $it) {
    $p = DB::table('supply_products')->where('id', $it->supply_product_id)->first();
    $ord = DB::table('orders')->where('id', $it->order_id)->first();
    $isSample = $ord && ($ord->order_type ?? 'normal') === 'sample';
    if (!$p) { $rec('ERROR', 'B1', "order_item#{$it->id}: 제품 없음"); continue; }

    // B1. 공급유형/공급처 일치
    if ($it->supply_type !== $p->supply_type)
        $rec('ERROR', 'B1', "order_item#{$it->id}: supply_type({$it->supply_type}) ≠ 제품({$p->supply_type})");
    $expSupplier = $p->supply_type === 'supplier' ? (int) $p->supplier_id : null;
    if ((int) ($it->supplier_id ?? 0) !== (int) ($expSupplier ?? 0))
        $rec('ERROR', 'B1', "order_item#{$it->id}: supplier_id({$it->supplier_id}) ≠ 제품 공급처({$expSupplier})");

    // B2. 단위 소속
    if ($it->supply_product_unit_id) {
        $u = DB::table('supply_product_units')->where('id', $it->supply_product_unit_id)->first();
        if (!$u || (int) $u->supply_product_id !== (int) $it->supply_product_id)
            $rec('ERROR', 'B2', "order_item#{$it->id}: 단위가 다른 제품 소속");
    }

    // B3. 금액 = 단가 × 수량
    if ((int) $it->store_line_amount !== (int) $it->store_unit_price * (int) $it->qty)
        $rec('ERROR', 'B3', "order_item#{$it->id}: store_line_amount 불일치");
    if ((int) $it->supply_line_amount !== (int) $it->supply_unit_price * (int) $it->qty)
        $rec('ERROR', 'B3', "order_item#{$it->id}: supply_line_amount 불일치");

    // B4. hq 품목은 공급가 0 (본사가 마진 전액)
    if (!$isSample && $it->supply_type === 'hq' && (int) $it->supply_unit_price !== 0)
        $rec('WARN', 'B4', "order_item#{$it->id}: hq 품목인데 supply_unit_price≠0");

    // B5. sales_order 연결 및 주체 일치
    if (!$it->sales_order_id) {
        $rec('WARN', 'B5', "order_item#{$it->id}: sales_order 미연결");
    } else {
        $so = DB::table('sales_orders')->where('id', $it->sales_order_id)->first();
        if (!$so) $rec('ERROR', 'B5', "order_item#{$it->id}: sales_order#{$it->sales_order_id} 없음");
        else {
            $expSeller = $it->supply_type === 'supplier' ? 'supplier' : 'hq';
            if ($so->seller_type !== $expSeller) $rec('ERROR', 'B5', "order_item#{$it->id}: SO seller_type({$so->seller_type}) ≠ 기대({$expSeller})");
            if ((int) ($so->supplier_id ?? 0) !== (int) ($it->supplier_id ?? 0)) $rec('ERROR', 'B5', "order_item#{$it->id}: SO supplier_id 불일치");
            if ($ord && (int) $so->store_id !== (int) $ord->store_id) $rec('ERROR', 'B5', "order_item#{$it->id}: SO store_id ≠ order store_id");
            if ($ord && (int) $so->order_id !== (int) $ord->id) $rec('ERROR', 'B5', "order_item#{$it->id}: SO order_id 불일치");
        }
    }
}

// B6. sales_order: 그룹 단일성 + 집계
foreach (DB::table('sales_orders')->get() as $so) {
    $items = DB::table('order_items')->where('sales_order_id', $so->id)->get();
    $ord = DB::table('orders')->where('id', $so->order_id)->first();
    if ($ord && (int) $so->store_id !== (int) $ord->store_id) $rec('ERROR', 'B6', "sales_order#{$so->id}: store_id ≠ order");
    foreach ($items as $i) {
        $iSeller = $i->supply_type === 'supplier' ? 'supplier' : 'hq';
        if ($iSeller !== $so->seller_type || (int) ($i->supplier_id ?? 0) !== (int) ($so->supplier_id ?? 0))
            $rec('ERROR', 'B6', "sales_order#{$so->id}: 다른 주체의 품목 포함(order_item#{$i->id})");
    }
    if ((int) $so->item_count !== $items->count()) $rec('WARN', 'B6', "sales_order#{$so->id}: item_count 불일치");
    if ((int) $so->store_amount !== (int) $items->sum('store_line_amount')) $rec('WARN', 'B6', "sales_order#{$so->id}: store_amount 불일치");
    if ((int) $so->supply_amount !== (int) $items->sum('supply_line_amount')) $rec('WARN', 'B6', "sales_order#{$so->id}: supply_amount 불일치");
}
// B6b. 주문별 (seller_type,supplier_id) 중복 sales_order
foreach (DB::table('sales_orders')->select('order_id', 'seller_type', 'supplier_id', DB::raw('count(*) c'))
    ->groupBy('order_id', 'seller_type', 'supplier_id')->having('c', '>', 1)->get() as $d)
    $rec('ERROR', 'B6', "order#{$d->order_id}: 동일 주체({$d->seller_type}/{$d->supplier_id}) sales_order {$d->c}개 (1개여야)");

// B7. shipment: 단일 주체 + 동일 매장
foreach (DB::table('shipments')->get() as $sh) {
    $items = DB::table('order_items')->where('shipment_id', $sh->id)->get();
    foreach ($items as $i) {
        $iSeller = $i->supply_type === 'supplier' ? 'supplier' : 'hq';
        if ($iSeller !== $sh->seller_type || (int) ($i->supplier_id ?? 0) !== (int) ($sh->supplier_id ?? 0))
            $rec('ERROR', 'B7', "shipment#{$sh->id}: 다른 주체 품목 포함(order_item#{$i->id})");
        $ord = DB::table('orders')->where('id', $i->order_id)->first();
        if ($ord && (int) $sh->store_id !== (int) $ord->store_id)
            $rec('ERROR', 'B7', "shipment#{$sh->id}: store_id ≠ 품목 주문 매장(order_item#{$i->id})");
    }
}

// B8. tax_invoice: 단일 공급처, supplier 품목만
foreach (DB::table('tax_invoices')->get() as $ti) {
    $items = DB::table('order_items')->where('tax_invoice_id', $ti->id)->get();
    foreach ($items as $i) {
        if ($i->supply_type !== 'supplier') $rec('ERROR', 'B8', "tax_invoice#{$ti->id}: supplier 아닌 품목 포함(order_item#{$i->id})");
        if ((int) ($i->supplier_id ?? 0) !== (int) $ti->supplier_id) $rec('ERROR', 'B8', "tax_invoice#{$ti->id}: 다른 공급처 품목(order_item#{$i->id})");
    }
}

echo "==================== PART C. 롤백 시뮬레이션 (실제 SalesOrderGenerator) ====================\n";
// 공급처별 대표 제품 1개씩 선택해 가상 주문 생성 → 3자 분배 검증 → 롤백
$store = DB::table('stores')->first();
$user = DB::table('users')->first();
$pick = [];
foreach (DB::table('supply_products')->where('supply_type', 'supplier')->where('is_active', 1)
    ->orderBy('supplier_id')->get()->groupBy('supplier_id') as $sid => $prods) {
    $pick[] = $prods->first();
}
if (!$store || count($pick) < 1) {
    $rec('WARN', 'C0', '시뮬레이션 불가 (매장/제품 부족)');
} else {
    DB::beginTransaction();
    try {
        $order = Order::create([
            'order_no' => 'PO-SIMUL-CHECK', 'store_id' => $store->id, 'user_id' => $user->id ?? null,
            'status' => 'pending', 'order_type' => 'normal',
        ]);
        $storeTotal = 0; $supplyTotal = 0; $expectSuppliers = [];
        foreach ($pick as $idx => $p) {
            $unit = DB::table('supply_product_units')->where('supply_product_id', $p->id)
                ->orderByDesc('is_default')->first();
            $qty = $idx + 2;
            $sp = (int) ($unit->store_price ?? $p->store_price);
            $up = (int) ($unit->supply_price ?? $p->supply_price);
            OrderItem::create([
                'order_id' => $order->id, 'supply_product_id' => $p->id,
                'supply_product_unit_id' => $unit->id ?? null, 'product_name' => $p->name,
                'unit' => $unit->name ?? $p->unit, 'supply_type' => $p->supply_type,
                'supplier_id' => $p->supplier_id, 'supplier_name' => 'sim', 'qty' => $qty,
                'store_unit_price' => $sp, 'supply_unit_price' => $up,
                'store_line_amount' => $sp * $qty, 'supply_line_amount' => $up * $qty,
                'fulfillment_status' => 'pending',
            ]);
            $storeTotal += $sp * $qty; $supplyTotal += $up * $qty;
            $expectSuppliers[$p->supplier_id] = true;
        }
        $order->update(['store_amount' => $storeTotal, 'supply_amount' => $supplyTotal]);

        (new SalesOrderGenerator())->generate($order);

        // 검증: 공급처 수 = sales_order 수
        $sos = DB::table('sales_orders')->where('order_id', $order->id)->get();
        $nSup = count($expectSuppliers);
        echo "  생성: 품목 " . count($pick) . "개 / 공급처 {$nSup}곳 → sales_order " . $sos->count() . "개\n";
        if ($sos->count() !== $nSup) $rec('ERROR', 'C1', "sales_order 수({$sos->count()}) ≠ 공급처 수({$nSup})");
        foreach ($sos as $so) {
            $its = DB::table('order_items')->where('sales_order_id', $so->id)->get();
            if ($so->seller_type !== 'supplier') $rec('ERROR', 'C2', "SO#{$so->id}: seller_type≠supplier");
            if ((int) $so->store_id !== (int) $store->id) $rec('ERROR', 'C2', "SO#{$so->id}: store_id 불일치");
            if ((int) $so->order_id !== (int) $order->id) $rec('ERROR', 'C2', "SO#{$so->id}: order_id 불일치");
            foreach ($its as $i)
                if ((int) $i->supplier_id !== (int) $so->supplier_id) $rec('ERROR', 'C2', "SO#{$so->id}: 타 공급처 품목 포함");
            if ((int) $so->item_count !== $its->count()) $rec('ERROR', 'C3', "SO#{$so->id}: item_count 불일치");
            if ((int) $so->store_amount !== (int) $its->sum('store_line_amount')) $rec('ERROR', 'C3', "SO#{$so->id}: store_amount 불일치");
            if ((int) $so->supply_amount !== (int) $its->sum('supply_line_amount')) $rec('ERROR', 'C3', "SO#{$so->id}: supply_amount 불일치");
        }
        // 주문 합계 = 모든 sales_order 합계
        if ((int) $order->store_amount !== (int) $sos->sum('store_amount')) $rec('ERROR', 'C4', '주문 store_amount ≠ Σsales_order');
        if ((int) $order->supply_amount !== (int) $sos->sum('supply_amount')) $rec('ERROR', 'C4', '주문 supply_amount ≠ Σsales_order');
        $unlinked = DB::table('order_items')->where('order_id', $order->id)->whereNull('sales_order_id')->count();
        if ($unlinked) $rec('ERROR', 'C5', "미연결 품목 {$unlinked}건");
        echo "  검증 통과 항목: 3자 분배/주체일치/금액집계/연결성\n";
    } finally {
        DB::rollBack();
        echo "  ↩ 롤백 완료 (DB 변경 없음)\n";
    }
}

echo "\n\n############### 결과 요약 ###############\n";
$by = ['ERROR' => [], 'WARN' => []];
foreach ($issues as $i) $by[$i['sev']][] = $i;
foreach (['ERROR', 'WARN'] as $sev) {
    echo "\n--- $sev (" . count($by[$sev]) . ") ---\n";
    foreach ($by[$sev] as $i) echo "  [{$i['code']}] {$i['msg']}\n";
}
echo "\n총 ERROR=" . count($by['ERROR']) . ", WARN=" . count($by['WARN']) . "\n";
