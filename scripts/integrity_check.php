<?php
// 데이터 정합성 점검 (읽기 전용). 실행: php scripts/integrity_check.php
error_reporting(E_ALL & ~E_DEPRECATED);
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$issues = [];
$rec = function (string $sev, string $code, string $msg) use (&$issues) {
    $issues[] = compact('sev', 'code', 'msg');
};

// 헬퍼: 고아 외래키 검사
$orphan = function (string $table, string $col, string $refTable, string $refCol = 'id', bool $nullable = true) use ($rec) {
    $q = DB::table($table)->whereNotNull("$table.$col")
        ->leftJoin($refTable, "$table.$col", '=', "$refTable.$refCol")
        ->whereNull("$refTable.$refCol");
    $rows = $q->limit(20)->pluck("$table.id")->all();
    $cnt = $q->count();
    if ($cnt > 0) {
        $rec('ERROR', 'ORPHAN', "$table.$col → $refTable: 고아 {$cnt}건 (id: " . implode(',', $rows) . ')');
    }
};

echo "==================== FK 참조 무결성 (고아 레코드) ====================\n";
$orphan('orders', 'store_id', 'stores');
$orphan('orders', 'user_id', 'users');
$orphan('order_items', 'order_id', 'orders');
$orphan('order_items', 'supply_product_id', 'supply_products');
$orphan('order_items', 'supply_product_unit_id', 'supply_product_units');
$orphan('order_items', 'supplier_id', 'suppliers');
$orphan('order_items', 'sales_order_id', 'sales_orders');
$orphan('order_items', 'shipment_id', 'shipments');
$orphan('order_items', 'tax_invoice_id', 'tax_invoices');
$orphan('sales_orders', 'order_id', 'orders');
$orphan('sales_orders', 'store_id', 'stores');
$orphan('sales_orders', 'supplier_id', 'suppliers');
$orphan('shipments', 'store_id', 'stores');
$orphan('shipments', 'supplier_id', 'suppliers');
$orphan('supply_products', 'supplier_id', 'suppliers');
$orphan('supply_product_units', 'supply_product_id', 'supply_products');
$orphan('supplier_unit_prices', 'supply_product_id', 'supply_products');
$orphan('supplier_unit_prices', 'supplier_id', 'suppliers');
$orphan('supplier_unit_prices', 'supply_product_unit_id', 'supply_product_units');
$orphan('store_inventories', 'store_id', 'stores');
$orphan('store_inventories', 'supply_product_id', 'supply_products');
$orphan('inventory_movements', 'store_id', 'stores');
$orphan('inventory_movements', 'supply_product_id', 'supply_products');
$orphan('inventory_movements', 'shipment_id', 'shipments');
$orphan('tax_invoices', 'supplier_id', 'suppliers');

echo "==================== 금액 합계 일치 ====================\n";
// order_items: line_amount == qty * unit_price
foreach (DB::table('order_items')->get() as $it) {
    $expStore = (int) $it->qty * (int) $it->store_unit_price;
    $expSupply = (int) $it->qty * (int) $it->supply_unit_price;
    if ((int) $it->store_line_amount !== $expStore) {
        $rec('WARN', 'LINE_AMT', "order_item#{$it->id}: store_line_amount={$it->store_line_amount} ≠ qty*store_unit_price={$expStore}");
    }
    if ((int) $it->supply_line_amount !== $expSupply) {
        $rec('WARN', 'LINE_AMT', "order_item#{$it->id}: supply_line_amount={$it->supply_line_amount} ≠ qty*supply_unit_price={$expSupply}");
    }
}
// orders: store_amount / supply_amount == sum of items
foreach (DB::table('orders')->get() as $o) {
    $sumS = (int) DB::table('order_items')->where('order_id', $o->id)->sum('store_line_amount');
    $sumP = (int) DB::table('order_items')->where('order_id', $o->id)->sum('supply_line_amount');
    if ((int) $o->store_amount !== $sumS) {
        $rec('WARN', 'ORDER_SUM', "order#{$o->id}({$o->order_no}): store_amount={$o->store_amount} ≠ Σitems={$sumS}");
    }
    if ((int) $o->supply_amount !== $sumP) {
        $rec('WARN', 'ORDER_SUM', "order#{$o->id}({$o->order_no}): supply_amount={$o->supply_amount} ≠ Σitems={$sumP}");
    }
}
// sales_orders: item_count / amounts == sum of its items
foreach (DB::table('sales_orders')->get() as $so) {
    $items = DB::table('order_items')->where('sales_order_id', $so->id);
    $cnt = (clone $items)->count();
    $sumS = (int) (clone $items)->sum('store_line_amount');
    $sumP = (int) (clone $items)->sum('supply_line_amount');
    if ((int) $so->item_count !== $cnt) {
        $rec('WARN', 'SO_COUNT', "sales_order#{$so->id}({$so->sales_order_no}): item_count={$so->item_count} ≠ 실제 {$cnt}");
    }
    if ((int) $so->store_amount !== $sumS) {
        $rec('WARN', 'SO_SUM', "sales_order#{$so->id}: store_amount={$so->store_amount} ≠ Σitems={$sumS}");
    }
    if ((int) $so->supply_amount !== $sumP) {
        $rec('WARN', 'SO_SUM', "sales_order#{$so->id}: supply_amount={$so->supply_amount} ≠ Σitems={$sumP}");
    }
}
// tax_invoices: total == supply + vat, and supply_amount == sum of linked items
foreach (DB::table('tax_invoices')->get() as $ti) {
    if ((int) $ti->total_amount !== (int) $ti->supply_amount + (int) $ti->vat) {
        $rec('WARN', 'TAX_TOTAL', "tax_invoice#{$ti->id}: total={$ti->total_amount} ≠ supply+vat=" . ((int) $ti->supply_amount + (int) $ti->vat));
    }
    $linked = (int) DB::table('order_items')->where('tax_invoice_id', $ti->id)->sum('supply_line_amount');
    if ($linked > 0 && (int) $ti->supply_amount !== $linked) {
        $rec('INFO', 'TAX_LINK', "tax_invoice#{$ti->id}: supply_amount={$ti->supply_amount} ≠ Σ연결품목={$linked}");
    }
}

echo "==================== 상태(enum) 유효성 ====================\n";
$enumCheck = function (string $table, string $col, array $valid) use ($rec) {
    $bad = DB::table($table)->whereNotNull($col)->whereNotIn($col, $valid)->limit(20)->pluck($col, 'id')->all();
    foreach ($bad as $id => $v) {
        $rec('ERROR', 'ENUM', "$table#$id: $col='$v' 는 허용값 아님 (" . implode('/', $valid) . ')');
    }
};
$enumCheck('orders', 'status', ['pending', 'processing', 'shipping', 'completed', 'canceled']);
$enumCheck('orders', 'order_type', ['normal', 'sample']);
$enumCheck('order_items', 'fulfillment_status', ['pending', 'shipping', 'delivered']);
$enumCheck('order_items', 'supply_type', ['hq', 'supplier']);
$enumCheck('sales_orders', 'status', ['created', 'confirmed', 'shipped', 'received', 'canceled']);
$enumCheck('sales_orders', 'seller_type', ['hq', 'supplier']);
$enumCheck('shipments', 'status', ['created', 'confirmed', 'received', 'canceled']);
$enumCheck('shipments', 'seller_type', ['hq', 'supplier']);
$enumCheck('supply_products', 'supply_type', ['hq', 'supplier']);
$enumCheck('supply_products', 'approval_status', ['approved', 'pending', 'rejected']);
$enumCheck('inventory_movements', 'type', ['in', 'out', 'adjust']);
$enumCheck('tax_invoices', 'status', ['issued', 'canceled']);

echo "==================== 공급유형 ↔ 공급처 일관성 ====================\n";
// supply_type='supplier' 인데 supplier_id 없음 / 'hq' 인데 supplier_id 있음
foreach (['order_items' => 'supply_type', 'supply_products' => 'supply_type'] as $t => $col) {
    $missing = DB::table($t)->where($col, 'supplier')->whereNull('supplier_id')->pluck('id')->all();
    if ($missing) $rec('ERROR', 'SUPPLY_TYPE', "$t: supply_type=supplier 인데 supplier_id 없음 (id: " . implode(',', $missing) . ')');
    $extra = DB::table($t)->where($col, 'hq')->whereNotNull('supplier_id')->pluck('id')->all();
    if ($extra) $rec('WARN', 'SUPPLY_TYPE', "$t: supply_type=hq 인데 supplier_id 존재 (id: " . implode(',', $extra) . ')');
}
// sales_orders / shipments: seller_type=supplier 인데 supplier_id 없음
foreach (['sales_orders', 'shipments'] as $t) {
    $missing = DB::table($t)->where('seller_type', 'supplier')->whereNull('supplier_id')->pluck('id')->all();
    if ($missing) $rec('ERROR', 'SELLER_TYPE', "$t: seller_type=supplier 인데 supplier_id 없음 (id: " . implode(',', $missing) . ')');
}

echo "==================== 주문 상태 ↔ 품목 이행상태 일관성 ====================\n";
foreach (DB::table('orders')->where('status', '!=', 'canceled')->get() as $o) {
    $items = DB::table('order_items')->where('order_id', $o->id)->get();
    if ($items->isEmpty()) {
        $rec('WARN', 'ORDER_EMPTY', "order#{$o->id}({$o->order_no}): 품목 없음 (status={$o->status})");
        continue;
    }
    $delivered = $items->where('fulfillment_status', 'delivered')->count();
    $shipping = $items->where('fulfillment_status', 'shipping')->count();
    if ($delivered === $items->count()) $expect = 'completed';
    elseif ($delivered > 0 || $shipping > 0) $expect = 'shipping';
    else $expect = 'pending';
    if ($o->status !== $expect && !($o->status === 'processing' && $expect === 'pending')) {
        $rec('INFO', 'ORDER_STATUS', "order#{$o->id}({$o->order_no}): status='{$o->status}' 인데 품목기준 기대값='{$expect}' (배송완료 {$delivered}/{$items->count()})");
    }
}

echo "==================== 품목 fulfillment 참조 일관성 ====================\n";
// shipping/delivered 인데 shipment_id 없음
$noShip = DB::table('order_items')->whereIn('fulfillment_status', ['shipping', 'delivered'])->whereNull('shipment_id')->pluck('id')->all();
if ($noShip) $rec('WARN', 'FULFILL_REF', 'order_items: fulfillment=shipping/delivered 인데 shipment_id 없음 (id: ' . implode(',', $noShip) . ')');
// shipment_id 있는데 sales_order_id 없음
$noSO = DB::table('order_items')->whereNotNull('shipment_id')->whereNull('sales_order_id')->pluck('id')->all();
if ($noSO) $rec('INFO', 'FULFILL_REF', 'order_items: shipment_id 있는데 sales_order_id 없음 (id: ' . implode(',', $noSO) . ')');

echo "==================== 재고 ↔ 이동내역 일관성 ====================\n";
// 최신 inventory_movement.balance_after 가 store_inventories.qty 와 일치하는지
foreach (DB::table('store_inventories')->get() as $si) {
    $last = DB::table('inventory_movements')
        ->where('store_id', $si->store_id)
        ->where('supply_product_id', $si->supply_product_id)
        ->where('supply_product_unit_id', $si->supply_product_unit_id)
        ->orderByDesc('id')->first();
    if ($last && (int) $last->balance_after !== (int) $si->qty) {
        $rec('WARN', 'INV_BAL', "store_inventory#{$si->id}: qty={$si->qty} ≠ 최신 이동 balance_after={$last->balance_after}");
    }
    if (!$last) {
        $rec('INFO', 'INV_NOMOVE', "store_inventory#{$si->id}: 대응 이동내역 없음 (qty={$si->qty})");
    }
}
// 음수 재고
$neg = DB::table('store_inventories')->where('qty', '<', 0)->pluck('qty', 'id')->all();
foreach ($neg as $id => $q) $rec('ERROR', 'INV_NEG', "store_inventory#$id: 음수 재고 qty=$q");

echo "==================== 유니크/중복 ====================\n";
$dupCheck = function (string $table, string $col) use ($rec) {
    $dups = DB::table($table)->select($col, DB::raw('count(*) as c'))->whereNotNull($col)->where($col, '!=', '')
        ->groupBy($col)->havingRaw('count(*) > 1')->get();
    foreach ($dups as $d) $rec('ERROR', 'DUP', "$table.$col 중복: '{$d->$col}' ({$d->c}건)");
};
$dupCheck('orders', 'order_no');
$dupCheck('sales_orders', 'sales_order_no');
$dupCheck('shipments', 'shipment_no');
$dupCheck('supply_products', 'code');
$dupCheck('supply_products', 'barcode');
$dupCheck('tax_invoices', 'invoice_no');
$dupCheck('users', 'email');
$dupCheck('suppliers', 'biz_no');

echo "==================== 기본단가/단위 정합성 ====================\n";
// 각 supply_product 에 is_default 단위가 정확히 1개인지
foreach (DB::table('supply_products')->get() as $p) {
    $units = DB::table('supply_product_units')->where('supply_product_id', $p->id);
    $total = (clone $units)->count();
    $defaults = (clone $units)->where('is_default', 1)->count();
    if ($total === 0) {
        $rec('WARN', 'UNIT', "supply_product#{$p->id}({$p->code}): 단위 없음");
    } elseif ($defaults === 0) {
        $rec('WARN', 'UNIT', "supply_product#{$p->id}({$p->code}): 기본단위(is_default) 없음 (단위 {$total}개)");
    } elseif ($defaults > 1) {
        $rec('ERROR', 'UNIT', "supply_product#{$p->id}({$p->code}): 기본단위 {$defaults}개 (1개여야 함)");
    }
}

echo "\n\n############### 결과 요약 ###############\n";
$by = ['ERROR' => [], 'WARN' => [], 'INFO' => []];
foreach ($issues as $i) $by[$i['sev']][] = $i;
foreach (['ERROR', 'WARN', 'INFO'] as $sev) {
    echo "\n--- $sev (" . count($by[$sev]) . ") ---\n";
    foreach ($by[$sev] as $i) echo "  [{$i['code']}] {$i['msg']}\n";
}
echo "\n총 ERROR=" . count($by['ERROR']) . ", WARN=" . count($by['WARN']) . ", INFO=" . count($by['INFO']) . "\n";
