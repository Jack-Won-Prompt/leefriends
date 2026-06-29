<?php

namespace App\Services\TaxInvoice;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Statement;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierStatement;
use App\Models\SupplyProduct;
use App\Models\TaxInvoice;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Popbill\PopbillTaxinvoiceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * 매장 발주(주문)를 기반으로 전자세금계산서를 생성하고 팝빌로 즉시발행한다.
 *  - 본사 → 매장 : 매장 구매가(store_line_amount) 기준
 *  - 공급처 → 본사 : 공급가(supply_line_amount, 원가) 기준
 * 부가세는 제품별 tax_type(inc/exc/exempt)으로 산출.
 * 테스트 모드(IsTest)에서는 발행자 사업자번호를 팝빌 테스트법인으로 치환.
 */
class TaxInvoiceIssueService
{
    public function __construct(
        private PopbillTaxinvoiceService $popbill,
        private NotificationService $notifications,
    ) {
    }

    /** 본사 → 매장 (발주 1건). 과세·면세 혼합 시 2장 발행 → Collection 반환. */
    public function hqToStore(Order $order, ?string $overrideEmail = null): Collection
    {
        $order->loadMissing('store');
        abort_unless($order->store, 404, '매장 정보가 없습니다.');

        return $this->hqToStoreOrders($order->store, collect([$order]), $overrideEmail);
    }

    /**
     * 본사 → 매장 (여러 발주를 한 매장 기준으로 합산, 발주별 분리 라인).
     * 과세 품목 → 세금계산서, 면세 품목 → 계산서로 자동 분리 발행.
     */
    public function hqToStoreOrders(Store $store, Collection $orders, ?string $overrideEmail = null): Collection
    {
        if (! $store->biz_no) {
            throw new \RuntimeException("«{$store->name}» 매장 사업자등록번호가 없습니다. 매장 관리에서 등록하세요.");
        }
        // 모든 발주가 같은 매장인지 검증
        foreach ($orders as $o) {
            if ((int) $o->store_id !== (int) $store->id) {
                throw new \RuntimeException('서로 다른 매장의 발주는 한 장으로 발행할 수 없습니다.');
            }
        }

        // 발주별 분리: 각 발주의 품목을 발주번호와 함께 라인으로 (발주일 순)
        $lines = [];
        foreach ($orders->sortBy('created_at') as $o) {
            foreach ($this->buildLines($o->items, 'store') as $line) {
                $line['order_no'] = $o->order_no;
                $lines[] = $line;
            }
            // 택배비(과세, 부가세 포함)도 한 라인으로 포함
            if ((int) $o->shipping_fee > 0) {
                [$supply, $tax] = SupplyProduct::taxBreakdown('inc', (int) $o->shipping_fee);
                $lines[] = [
                    'item_id' => null,
                    'name' => '택배비',
                    'spec' => '',
                    'qty' => 1,
                    'unit_price' => (int) $o->shipping_fee,
                    'tax_type' => 'inc',
                    'supply' => $supply,
                    'tax' => $tax,
                    'order_no' => $o->order_no,
                ];
            }
        }

        $invoicer = $this->hqParty();
        $invoicee = [
            'corp_num' => $this->digits($store->biz_no),
            'corp_name' => $store->name,
            'ceo' => $store->ceo ?: $store->name,
            'addr' => $this->storeAddr($store),
            'biz_type' => $store->biz_type ?: '',
            'biz_class' => $store->biz_class ?: '',
            'contact' => $store->name,
            'tel' => $store->phone ?: '',
            'email' => $overrideEmail ?: ($store->email ?: ''),
        ];

        return $this->issueSplit('hq_to_store', $lines, $invoicer, $invoicee, [
            'store_id' => $store->id,
            'order_id' => $orders->count() === 1 ? $orders->first()->id : null,
            'supplier_id' => null,
            'order_ids' => $orders->pluck('id')->all(),
        ]);
    }

    /**
     * 본사 → 매장 (거래명세서 기반). 거래명세서 품목 스냅샷으로 발행.
     * 과세 품목 → 세금계산서, 면세 품목 → 계산서 자동 분리.
     */
    public function hqToStoreFromStatement(Statement $statement): Collection
    {
        $store = $statement->store;
        abort_unless($store, 404, '거래명세서의 매장 정보가 없습니다.');
        if (! $store->biz_no) {
            throw new \RuntimeException("«{$store->name}» 매장 사업자등록번호가 없습니다. 매장 관리에서 등록하세요.");
        }

        $lines = $this->buildLinesFromStatement($statement->items ?? []);
        $invoicer = $this->hqParty();
        $invoicee = [
            'corp_num' => $this->digits($store->biz_no),
            'corp_name' => $store->name,
            'ceo' => $store->ceo ?: $store->name,
            'addr' => $this->storeAddr($store),
            'biz_type' => $store->biz_type ?: '',
            'biz_class' => $store->biz_class ?: '',
            'contact' => $store->name,
            'tel' => $store->phone ?: '',
            'email' => $store->email ?: '',
        ];

        return $this->issueSplit('hq_to_store', $lines, $invoicer, $invoicee, [
            'store_id' => $store->id,
            'order_id' => null,
            'supplier_id' => null,
            'statement_id' => $statement->id,
        ]);
    }

    /** 거래명세서 품목(code/name/unit/qty/price/amount) → 세금계산서 라인. 부가세구분은 code로 조회. */
    private function buildLinesFromStatement(array $items): array
    {
        $codes = array_filter(array_column($items, 'code'));
        $taxTypes = SupplyProduct::whereIn('code', $codes)->pluck('tax_type', 'code');

        $lines = [];
        foreach ($items as $it) {
            $amount = (int) ($it['amount'] ?? 0);
            $taxType = $taxTypes[$it['code'] ?? ''] ?? 'inc';
            [$supply, $tax] = SupplyProduct::taxBreakdown($taxType, $amount);
            $lines[] = [
                'item_id' => null,
                'name' => $it['name'] ?? '-',
                'spec' => $it['unit'] ?? '',
                'qty' => (int) ($it['qty'] ?? 0),
                'unit_price' => (int) ($it['price'] ?? 0),
                'tax_type' => $taxType,
                'supply' => $supply,
                'tax' => $tax,
            ];
        }

        return $lines;
    }

    /** 공급처 → 본사 (특정 공급처의 주문 품목들). 과세·면세 혼합 시 2장 발행 → Collection 반환. */
    public function supplierToHq(Supplier $supplier, Collection $orderItems, ?Order $order = null, ?string $overrideEmail = null): Collection
    {
        $lines = $this->buildLines($orderItems, 'supply');

        return $this->issueSplit('supplier_to_hq', $lines, $this->supplierParty($supplier), $this->hqParty($overrideEmail), [
            'supplier_id' => $supplier->id, 'order_id' => optional($order)->id, 'store_id' => optional($order)->store_id,
        ]);
    }

    /**
     * 공급처 → 본사 (거래명세서 기반, 자유 작성). 거래명세서 품목 스냅샷으로 발행.
     * 과세 품목 → 세금계산서, 면세 품목 → 계산서 자동 분리.
     */
    public function supplierToHqFromStatement(SupplierStatement $statement): Collection
    {
        abort_unless($statement->supplier, 404, '거래명세서의 공급처 정보가 없습니다.');

        return $this->supplierToHqFromStatements($statement->supplier, collect([$statement]));
    }

    /** 공급처 → 본사 (여러 거래명세서 합산). 과세·면세 자동 분리. */
    public function supplierToHqFromStatements(Supplier $supplier, Collection $statements): Collection
    {
        $lines = [];
        foreach ($statements as $st) {
            foreach ($st->items ?? [] as $l) {
                $lines[] = [
                    'item_id' => null,
                    'name' => $l['name'] ?? '-',
                    'spec' => $l['unit'] ?? '',
                    'qty' => (int) ($l['qty'] ?? 0),
                    'unit_price' => (int) ($l['unit_price'] ?? 0),
                    'tax_type' => $l['tax_type'] ?? 'inc',
                    'supply' => (int) ($l['supply'] ?? 0),
                    'tax' => (int) ($l['tax'] ?? 0),
                ];
            }
        }

        return $this->issueSplit('supplier_to_hq', $lines, $this->supplierParty($supplier), $this->hqParty(), [
            'supplier_id' => $supplier->id,
        ]);
    }

    private function supplierParty(Supplier $supplier): array
    {
        return [
            'corp_num' => $this->digits($supplier->biz_no),
            'corp_name' => $supplier->name,
            'ceo' => $supplier->ceo ?: $supplier->name,
            'addr' => trim(($supplier->address ?? '').' '.($supplier->address_detail ?? '')),
            'biz_type' => '', 'biz_class' => '',
            'contact' => $supplier->name,
            'tel' => $supplier->phone ?: '',
            'email' => $supplier->email ?: '',
        ];
    }

    /**
     * 발행취소. 팝빌 CancelIssue 후 상태=취소, 연결된 발주/품목을 해제(재발행 가능).
     * ※ 국세청 전송 완료 후에는 취소 불가(수정세금계산서 발행 대상) — 팝빌 오류를 그대로 전달.
     */
    public function cancel(TaxInvoice $invoice, ?string $memo = null): TaxInvoice
    {
        if ($invoice->status === 'canceled') {
            throw new \RuntimeException('이미 취소된 세금계산서입니다.');
        }
        if (! $invoice->popbill_mgt_key) {
            throw new \RuntimeException('팝빌 문서번호가 없어 취소할 수 없습니다.');
        }

        $apiCorp = $this->apiCorpNum($invoice->invoicer_corp_num);
        $result = $this->popbill->cancelIssue($apiCorp, $invoice->popbill_mgt_key, $memo ?: '발행취소', $this->userId());
        if (($result->code ?? 0) != 1) {
            throw new \RuntimeException('팝빌 발행취소 실패: '.($result->message ?? '알 수 없는 오류'));
        }

        $invoice->update(['status' => 'canceled']);

        // 재발행 가능하도록 연결 해제
        if ($invoice->direction === 'hq_to_store') {
            Order::where('tax_invoice_id', $invoice->id)->update(['tax_invoice_id' => null]);
        } elseif ($invoice->direction === 'supplier_to_hq') {
            OrderItem::where('tax_invoice_id', $invoice->id)->update(['tax_invoice_id' => null]);
        }

        $this->notifyCanceled($invoice);

        return $invoice;
    }

    /** 세금계산서 발행 알림 (웹 토스트 + 모바일 FCM). 실패해도 발행은 막지 않음. */
    private function notifyIssued(string $direction, array $refs, Collection $issued): void
    {
        if ($issued->isEmpty()) {
            return;
        }
        try {
            $total = (int) $issued->sum('total_amount');
            $first = $issued->first();
            $data = ['invoice_id' => $first->id, 'invoice_no' => $first->invoice_no];

            if ($direction === 'hq_to_store' && ! empty($refs['store_id'])) {
                $this->notifications->notifyStore(
                    (int) $refs['store_id'],
                    'tax_invoice_issued',
                    '🧾 세금계산서가 발행되었습니다',
                    '본사에서 세금계산서를 발행했습니다 · 합계 '.number_format($total).'원',
                    $data,
                );
            } elseif ($direction === 'supplier_to_hq') {
                $this->notifications->notifyUsers(
                    User::where('role', 'hq')->get(),
                    'tax_invoice_issued',
                    '🧾 공급처 세금계산서 발행',
                    ($first->invoicer_corp_name ?: '공급처').' · 합계 '.number_format($total).'원 청구 세금계산서가 발행되었습니다',
                    $data,
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** 세금계산서 취소 알림 (상대방에게). */
    private function notifyCanceled(TaxInvoice $invoice): void
    {
        try {
            $data = ['invoice_id' => $invoice->id, 'invoice_no' => $invoice->invoice_no];

            if ($invoice->direction === 'hq_to_store' && $invoice->store_id) {
                $this->notifications->notifyStore(
                    (int) $invoice->store_id,
                    'tax_invoice_canceled',
                    '⚠️ 세금계산서 취소',
                    "세금계산서 {$invoice->invoice_no}이(가) 발행취소되었습니다.",
                    $data,
                );
            } elseif ($invoice->direction === 'supplier_to_hq') {
                $this->notifications->notifyUsers(
                    User::where('role', 'hq')->get(),
                    'tax_invoice_canceled',
                    '⚠️ 공급처 세금계산서 취소',
                    ($invoice->invoicer_corp_name ?: '공급처')." 세금계산서 {$invoice->invoice_no}이(가) 취소되었습니다.",
                    $data,
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** 주문 품목 → 세금계산서 라인 (mode: store=매장구매가 / supply=공급가) */
    private function buildLines(Collection $items, string $mode): array
    {
        $taxTypes = SupplyProduct::whereIn('id', $items->pluck('supply_product_id'))->pluck('tax_type', 'id');
        $lines = [];
        foreach ($items as $it) {
            $amount = (int) ($mode === 'store' ? $it->store_line_amount : $it->supply_line_amount);
            $unitPrice = (int) ($mode === 'store' ? $it->store_unit_price : $it->supply_unit_price);
            $taxType = $taxTypes[$it->supply_product_id] ?? 'inc';
            [$supply, $tax] = SupplyProduct::taxBreakdown($taxType, $amount);
            $lines[] = [
                'item_id' => $it->id,
                'name' => $it->product_name,
                'spec' => $it->unit,
                'qty' => (int) $it->qty,
                'unit_price' => $unitPrice,
                'tax_type' => $taxType,
                'supply' => $supply,
                'tax' => $tax,
            ];
        }

        return $lines;
    }

    /**
     * 과세(inc/exc)·면세(exempt) 품목을 분리해 각각 세금계산서·계산서로 발행.
     * 둘 다 있으면 2장, 한 종류만 있으면 1장. 발행된 문서들을 Collection 으로 반환.
     */
    private function issueSplit(string $direction, array $lines, array $invoicer, array $invoicee, array $refs): Collection
    {
        abort_if(empty($lines), 422, '발행할 품목이 없습니다.');

        $groups = [
            // 과세: 부가세 포함/별도 모두 → 세금계산서
            '과세' => array_values(array_filter($lines, fn ($l) => $l['tax_type'] !== 'exempt')),
            // 면세 → 계산서
            '면세' => array_values(array_filter($lines, fn ($l) => $l['tax_type'] === 'exempt')),
        ];

        $issued = collect();
        $idx = 0;
        foreach ($groups as $taxType => $glines) {
            if (empty($glines)) {
                continue;
            }
            $invoice = $this->createAndIssueOne($direction, $taxType, $glines, $invoicer, $invoicee, $refs, ++$idx);
            $issued->push($invoice);

            // 공급처→본사: 해당 그룹 품목을 발행 처리(미청구 필터용)
            if ($direction === 'supplier_to_hq') {
                $itemIds = array_filter(array_column($glines, 'item_id'));
                if ($itemIds) {
                    OrderItem::whereIn('id', $itemIds)->update(['tax_invoice_id' => $invoice->id]);
                }
            }
        }

        // 본사→매장: 포함된 발주들을 발행 처리(재발행 방지). 대표 문서 = 과세(없으면 면세)
        if ($direction === 'hq_to_store' && ! empty($refs['order_ids'])) {
            Order::whereIn('id', $refs['order_ids'])->update(['tax_invoice_id' => $issued->first()->id]);
        }
        // 거래명세서 기반 발행: 거래명세서를 발행 처리
        if ($direction === 'hq_to_store' && ! empty($refs['statement_id'])) {
            Statement::where('id', $refs['statement_id'])->update(['tax_invoice_id' => $issued->first()->id]);
        }

        $this->notifyIssued($direction, $refs, $issued);

        return $issued;
    }

    /** 단일 문서(세금계산서 또는 계산서) 발행 + 로컬 기록 */
    private function createAndIssueOne(string $direction, string $taxType, array $lines, array $invoicer, array $invoicee, array $refs, int $seq): TaxInvoice
    {
        $supplyTotal = array_sum(array_column($lines, 'supply'));
        $taxTotal = array_sum(array_column($lines, 'tax'));
        $total = $supplyTotal + $taxTotal;
        $docLabel = $taxType === '면세' ? '계산서(면세)' : '세금계산서(과세)';

        $invoiceNo = $this->invoiceNo($seq);
        $mgtKey = $invoiceNo; // 팝빌 문서관리번호 = 계산서번호(영숫자, 유니크)

        // ── 팝빌 Taxinvoice 구성 ──
        $apiCorp = $this->apiCorpNum($invoicer['corp_num']);
        $inv = $this->popbill->newInvoice();
        $inv->writeDate = now()->format('Ymd');
        $inv->chargeDirection = '정과금';
        $inv->issueType = '정발행';
        $inv->purposeType = '영수';
        $inv->taxType = $taxType; // 과세 / 면세

        $inv->invoicerCorpNum = $apiCorp;
        $inv->invoicerMgtKey = $mgtKey;
        $inv->invoicerCorpName = $invoicer['corp_name'];
        $inv->invoicerCEOName = $invoicer['ceo'];
        $inv->invoicerAddr = $invoicer['addr'];
        $inv->invoicerBizType = $invoicer['biz_type'];
        $inv->invoicerBizClass = $invoicer['biz_class'];
        $inv->invoicerContactName = $invoicer['contact'];
        $inv->invoicerTEL = $invoicer['tel'];
        $inv->invoicerEmail = $invoicer['email'];

        $inv->invoiceeType = '사업자';
        $inv->invoiceeCorpNum = $invoicee['corp_num'];
        $inv->invoiceeCorpName = $invoicee['corp_name'];
        $inv->invoiceeCEOName = $invoicee['ceo'];
        $inv->invoiceeAddr = $invoicee['addr'];
        $inv->invoiceeBizType = $invoicee['biz_type'];
        $inv->invoiceeBizClass = $invoicee['biz_class'];
        $inv->invoiceeContactName1 = $invoicee['contact'];
        $inv->invoiceeTEL1 = $invoicee['tel'];
        $inv->invoiceeEmail1 = $invoicee['email'];

        $inv->supplyCostTotal = (string) $supplyTotal;
        $inv->taxTotal = (string) $taxTotal;
        $inv->totalAmount = (string) $total;

        $details = [];
        foreach ($lines as $i => $l) {
            $d = $this->popbill->newDetail();
            $d->serialNum = (string) ($i + 1);
            $d->itemName = $l['name'];
            $d->spec = $l['spec'];
            $d->qty = (string) $l['qty'];
            $d->unitCost = (string) $l['unit_price'];
            $d->supplyCost = (string) $l['supply'];
            $d->tax = (string) $l['tax'];
            $details[] = $d;
        }
        $inv->detailList = $details;

        // ── 발행 ──
        $result = $this->popbill->registIssue($apiCorp, $inv, $this->userId(), true);
        if (($result->code ?? 0) != 1) {
            throw new \RuntimeException('팝빌 발행 실패: '.($result->message ?? '알 수 없는 오류'));
        }

        // 국세청 승인번호 조회(운영 모드에서 채워짐)
        $ntsConfirmNum = null;
        try {
            $info = $this->popbill->getInfo($apiCorp, $mgtKey);
            $ntsConfirmNum = $info->ntsConfirmNum ?? null;
        } catch (\Throwable $e) {
            // 무시
        }

        return TaxInvoice::create([
            'invoice_no' => $invoiceNo,
            'direction' => $direction,
            'supplier_id' => $refs['supplier_id'] ?? null,
            'store_id' => $refs['store_id'] ?? null,
            'order_id' => $refs['order_id'] ?? null,
            'invoicer_corp_num' => $invoicer['corp_num'],
            'invoicer_corp_name' => $invoicer['corp_name'],
            'invoicee_corp_num' => $invoicee['corp_num'],
            'invoicee_corp_name' => $invoicee['corp_name'],
            'invoicee_email' => $invoicee['email'],
            'line_items' => $lines,
            'issued_by' => Auth::id(),
            'supply_amount' => $supplyTotal,
            'vat' => $taxTotal,
            'total_amount' => $total,
            'status' => 'issued',
            'provider' => 'popbill',
            'nts_confirm_num' => $ntsConfirmNum,
            'popbill_mgt_key' => $mgtKey,
            'issue_date' => now()->toDateString(),
            'note' => $docLabel,
        ]);
    }

    /** 본사(오다네트웍스) 발행/수신 당사자 정보 */
    private function hqParty(?string $overrideEmail = null): array
    {
        return [
            'corp_num' => $this->digits(config('popbill.hq.corp_num')),
            'corp_name' => config('popbill.hq.corp_name'),
            'ceo' => config('popbill.hq.ceo_name'),
            'addr' => config('popbill.hq.addr'),
            'biz_type' => config('popbill.hq.biz_type'),
            'biz_class' => config('popbill.hq.biz_class'),
            'contact' => '본사',
            'tel' => config('popbill.hq.tel') ?: '',
            'email' => $overrideEmail ?: (config('popbill.hq.email') ?: ''),
        ];
    }

    /** 테스트 모드면 팝빌 테스트법인으로 치환(테스트 환경에 등록된 corp만 발행 가능) */
    private function apiCorpNum(string $realCorpNum): string
    {
        return config('popbill.IsTest') ? $this->digits(config('popbill.test.corp_num')) : $this->digits($realCorpNum);
    }

    private function userId(): ?string
    {
        return config('popbill.test.user_id');
    }

    private function digits(?string $v): string
    {
        return preg_replace('/[^0-9]/', '', (string) $v);
    }

    private function storeAddr($store): string
    {
        $base = $store->corp_address ?: $store->address ?: '';
        $detail = $store->corp_address ? $store->corp_address_detail : $store->address_detail;

        return trim($base.' '.($detail ?? ''));
    }

    /** 팝빌 문서번호(영구 유일)·계산서번호. 밀리초+분리순번 포함해 재사용/동시발행 충돌 방지. */
    private function invoiceNo(int $seq = 1): string
    {
        return 'TI'.now()->format('YmdHisv').$seq; // 예: TI202606241408461231
    }
}
