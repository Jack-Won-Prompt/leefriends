<?php

namespace App\Services\TaxInvoice;

use App\Models\Order;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use App\Models\TaxInvoice;
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
    public function __construct(private PopbillTaxinvoiceService $popbill)
    {
    }

    /** 본사 → 매장 (발주 1건) */
    public function hqToStore(Order $order, ?string $overrideEmail = null): TaxInvoice
    {
        $order->loadMissing('store');
        abort_unless($order->store, 404, '매장 정보가 없습니다.');

        return $this->hqToStoreOrders($order->store, collect([$order]), $overrideEmail);
    }

    /**
     * 본사 → 매장 (여러 발주를 한 매장 기준으로 합산, 발주별 분리 라인).
     */
    public function hqToStoreOrders(Store $store, Collection $orders, ?string $overrideEmail = null): TaxInvoice
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

        return $this->createAndIssue('hq_to_store', $lines, $invoicer, $invoicee, [
            'store_id' => $store->id,
            'order_id' => $orders->count() === 1 ? $orders->first()->id : null,
            'supplier_id' => null,
            'order_ids' => $orders->pluck('id')->all(),
        ]);
    }

    /** 공급처 → 본사 (특정 공급처의 주문 품목들) */
    public function supplierToHq(Supplier $supplier, Collection $orderItems, ?Order $order = null, ?string $overrideEmail = null): TaxInvoice
    {
        $lines = $this->buildLines($orderItems, 'supply');
        $invoicer = [
            'corp_num' => $this->digits($supplier->biz_no),
            'corp_name' => $supplier->name,
            'ceo' => $supplier->ceo ?: $supplier->name,
            'addr' => trim(($supplier->address ?? '').' '.($supplier->address_detail ?? '')),
            'biz_type' => '', 'biz_class' => '',
            'contact' => $supplier->name,
            'tel' => $supplier->phone ?: '',
            'email' => $supplier->email ?: '',
        ];
        $invoicee = $this->hqParty($overrideEmail);

        return $this->createAndIssue('supplier_to_hq', $lines, $invoicer, $invoicee, [
            'supplier_id' => $supplier->id, 'order_id' => optional($order)->id, 'store_id' => optional($order)->store_id,
        ]);
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

    private function createAndIssue(string $direction, array $lines, array $invoicer, array $invoicee, array $refs): TaxInvoice
    {
        abort_if(empty($lines), 422, '세금계산서 품목이 없습니다.');

        $supplyTotal = array_sum(array_column($lines, 'supply'));
        $taxTotal = array_sum(array_column($lines, 'tax'));
        $total = $supplyTotal + $taxTotal;
        $allExempt = collect($lines)->every(fn ($l) => $l['tax_type'] === 'exempt');

        $invoiceNo = $this->invoiceNo();
        $mgtKey = $invoiceNo; // 팝빌 문서관리번호 = 계산서번호(영숫자, 유니크)

        // ── 팝빌 Taxinvoice 구성 ──
        $apiCorp = $this->apiCorpNum($invoicer['corp_num']);
        $inv = $this->popbill->newInvoice();
        $inv->writeDate = now()->format('Ymd');
        $inv->chargeDirection = '정과금';
        $inv->issueType = '정발행';
        $inv->purposeType = '영수';
        $inv->taxType = $allExempt ? '면세' : '과세';

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

        $invoice = TaxInvoice::create([
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
        ]);

        // 본사→매장: 포함된 발주들을 발행 처리(재발행 방지)
        if ($direction === 'hq_to_store' && ! empty($refs['order_ids'])) {
            Order::whereIn('id', $refs['order_ids'])->update(['tax_invoice_id' => $invoice->id]);
        }

        return $invoice;
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

    /** 팝빌 문서번호(영구 유일)·계산서번호. 밀리초까지 포함해 재사용 충돌 방지. */
    private function invoiceNo(): string
    {
        return 'TI'.now()->format('YmdHisv'); // 예: TI20260624140846123
    }
}
