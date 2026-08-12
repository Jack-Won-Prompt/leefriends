<?php

namespace App\Services\Export;

use App\Models\Store;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * 매장별 입금내역 + 거래내역(원장)을 고급 스타일 .xlsx 워크북으로 생성.
 *  - 시트1 «입금내역»: 발주 단위 입금완료/미입금
 *  - 시트2 «거래내역»: 예치금 원장 타임라인
 */
class StoreLedgerExcel
{
    private const DARK = 'FF1F2937';       // 헤더 배경(진회색)
    private const MANGO = 'FFF59E0B';      // 브랜드 포인트
    private const MANGO_SOFT = 'FFFFF3C4'; // 요약 배경
    private const LINE = 'FFE5E7EB';       // 테두리
    private const ZEBRA = 'FFF9FAFB';      // 줄무늬
    private const GREEN = 'FF15803D';
    private const RED = 'FFDC2626';
    private const WHITE = 'FFFFFFFF';
    private const MUTED = 'FF6B7280';

    /**
     * @param  Collection  $orders   입금내역 대상 발주 (created_at desc)
     * @param  Collection  $entries  거래내역(원장) 엔트리
     * @param  array       $meta     ['period_label'=>string]
     */
    public function build(Store $store, Collection $orders, Collection $entries, array $meta = []): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('LEEFRIENDS 본사')
            ->setTitle("{$store->name} 거래·입금 내역서")
            ->setCompany('주식회사 오다네트웍스');

        $this->buildPaymentsSheet($book->getActiveSheet(), $store, $orders, $meta);
        $this->buildLedgerSheet($book->createSheet(), $store, $entries, $meta);

        $book->setActiveSheetIndex(0);

        return $book;
    }

    /**
     * 조회 기간의 전 매장 주문서 리스트를 한 워크북으로.
     *  - 시트1 «주문서 리스트»: 전 매장 발주 상세
     *  - 시트2 «매장별 요약»: 매장 단위 집계
     *
     * @param  Collection  $orders   store 관계 + items_count 포함, 기간 필터 적용됨
     * @param  Collection  $byStore  매장별 집계 (id,name,region,cnt,total,paid,unpaid_cnt)
     */
    public function buildOrderList(Collection $orders, Collection $byStore, array $meta = []): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('LEEFRIENDS 본사')
            ->setTitle('매장 주문서 리스트')
            ->setCompany('주식회사 오다네트웍스');

        $this->orderListSheet($book->getActiveSheet(), $orders, $meta);
        $this->storeSummarySheet($book->createSheet(), $byStore, $meta);
        $book->setActiveSheetIndex(0);

        return $book;
    }

    /** 시트: 주문서 리스트 (전 매장) */
    private function orderListSheet(Worksheet $s, Collection $orders, array $meta): void
    {
        $s->setTitle('주문서 리스트');
        $s->setShowGridlines(false);
        $last = 'K';

        $total = 0;
        $paid = 0;
        foreach ($orders as $o) {
            $amt = (int) $o->store_amount + (int) $o->store_vat + (int) $o->shipping_fee;
            $total += $amt;
            if ($o->paid_at) {
                $paid += $amt;
            }
        }

        $period = $meta['period_label'] ?? '전체 기간';
        $this->titleBlockText($s, $last, '주 문 서 리 스 트', "기간: {$period}    |    발행: " . now()->format('Y-m-d H:i') . "    |    총 " . $orders->count() . '건');

        $this->summaryRow($s, 5, [
            ['총 발주액', $total, self::DARK],
            ['입금완료', $paid, self::GREEN],
            ['미입금', $total - $paid, self::RED],
            ['발주건수', $orders->count() . ' 건', self::MANGO, true],
        ], $last);

        $head = 7;
        $cols = ['매장', '지역', '발주일', '발주번호', '품목수', '공급가액', '부가세', '택배비', '합계', '입금', '입금일'];
        foreach ($cols as $i => $label) {
            $s->setCellValue([$i + 1, $head], $label);
        }
        $this->headerRow($s, "A{$head}:{$last}{$head}");

        $r = $head + 1;
        foreach ($orders as $idx => $o) {
            $amt = (int) $o->store_amount + (int) $o->store_vat + (int) $o->shipping_fee;
            $s->setCellValue([1, $r], optional($o->store)->name ?? '-');
            $s->setCellValue([2, $r], optional($o->store)->region ?: '-');
            $s->setCellValue([3, $r], optional($o->created_at)->format('Y-m-d'));
            $s->setCellValue([4, $r], $o->order_no);
            $s->setCellValue([5, $r], (int) ($o->items_count ?? 0));
            $s->setCellValue([6, $r], (int) $o->store_amount);
            $s->setCellValue([7, $r], (int) $o->store_vat);
            $s->setCellValue([8, $r], (int) $o->shipping_fee);
            $s->setCellValue([9, $r], $amt);
            $s->setCellValue([10, $r], $o->paid_at ? '입금완료' : '미입금');
            $s->setCellValue([11, $r], $o->paid_at ? \Illuminate\Support\Carbon::parse($o->paid_at)->format('Y-m-d') : '-');

            $this->bodyRow($s, $r, $last, $idx);
            $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $s->getStyle("J{$r}")->getFont()->setBold(true)->getColor()->setARGB($o->paid_at ? self::GREEN : self::RED);
            $s->getStyle("J{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $r++;
        }
        if ($orders->isEmpty()) {
            $this->emptyRow($s, $r, $last, '해당 기간의 발주 내역이 없습니다.');
            $r++;
        }

        // 합계
        $s->setCellValue("A{$r}", '합계');
        $s->mergeCells("A{$r}:E{$r}");
        $s->setCellValue("F{$r}", $orders->sum(fn ($o) => (int) $o->store_amount));
        $s->setCellValue("G{$r}", $orders->sum(fn ($o) => (int) $o->store_vat));
        $s->setCellValue("H{$r}", $orders->sum(fn ($o) => (int) $o->shipping_fee));
        $s->setCellValue("I{$r}", $total);
        $s->setCellValue("J{$r}", number_format($total) . '원');
        $s->mergeCells("J{$r}:K{$r}");
        $this->totalRow($s, $r, $last);
        $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $s->getStyle("E8:I{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $this->finishSheet($s, $last, $head, [16, 10, 13, 18, 8, 13, 11, 11, 14, 11, 13]);
        $s->freezePane("A" . ($head + 1));
    }

    /** 시트: 매장별 요약 */
    private function storeSummarySheet(Worksheet $s, Collection $byStore, array $meta): void
    {
        $s->setTitle('매장별 요약');
        $s->setShowGridlines(false);
        $last = 'G';

        $sumTotal = (int) $byStore->sum('total');
        $sumPaid = (int) $byStore->sum('paid');

        $period = $meta['period_label'] ?? '전체 기간';
        $this->titleBlockText($s, $last, '매 장 별 요 약', "기간: {$period}    |    발행: " . now()->format('Y-m-d H:i') . '    |    ' . $byStore->count() . '개 매장');

        $this->summaryRow($s, 5, [
            ['매장 수', $byStore->count() . ' 개', self::DARK, true],
            ['총 발주액', $sumTotal, self::DARK],
            ['입금완료', $sumPaid, self::GREEN],
            ['미입금', $sumTotal - $sumPaid, self::RED],
        ], $last);

        $head = 7;
        $cols = ['매장', '지역', '발주건수', '총 발주액', '입금완료', '미입금', '미입금건수'];
        foreach ($cols as $i => $label) {
            $s->setCellValue([$i + 1, $head], $label);
        }
        $this->headerRow($s, "A{$head}:{$last}{$head}");

        $r = $head + 1;
        foreach ($byStore as $idx => $b) {
            $unpaidAmt = (int) $b->total - (int) $b->paid;
            $s->setCellValue([1, $r], $b->name);
            $s->setCellValue([2, $r], $b->region ?: '-');
            $s->setCellValue([3, $r], (int) $b->cnt);
            $s->setCellValue([4, $r], (int) $b->total);
            $s->setCellValue([5, $r], (int) $b->paid);
            $s->setCellValue([6, $r], $unpaidAmt);
            $s->setCellValue([7, $r], (int) $b->unpaid_cnt);

            $this->bodyRow($s, $r, $last, $idx);
            $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            if ($unpaidAmt > 0) {
                $s->getStyle("F{$r}")->getFont()->setBold(true)->getColor()->setARGB(self::RED);
            }
            $r++;
        }
        if ($byStore->isEmpty()) {
            $this->emptyRow($s, $r, $last, '해당 기간의 매장 집계가 없습니다.');
            $r++;
        }

        $s->setCellValue("A{$r}", '합계');
        $s->mergeCells("A{$r}:C{$r}");
        $s->setCellValue("D{$r}", $sumTotal);
        $s->setCellValue("E{$r}", $sumPaid);
        $s->setCellValue("F{$r}", $sumTotal - $sumPaid);
        $s->setCellValue("G{$r}", (int) $byStore->sum('unpaid_cnt'));
        $this->totalRow($s, $r, $last);
        $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $s->getStyle("C8:F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $this->finishSheet($s, $last, $head, [20, 12, 12, 15, 15, 15, 12]);
        $s->freezePane("A" . ($head + 1));
    }

    /* ============================ 시트1: 입금내역 ============================ */

    private function buildPaymentsSheet(Worksheet $s, Store $store, Collection $orders, array $meta): void
    {
        $s->setTitle('입금내역');
        $s->setShowGridlines(false);
        $last = 'H';

        // 총계
        $total = 0;
        $paid = 0;
        foreach ($orders as $o) {
            $amt = (int) $o->store_amount + (int) $o->store_vat + (int) $o->shipping_fee;
            $total += $amt;
            if ($o->paid_at) {
                $paid += $amt;
            }
        }
        $unpaid = $total - $paid;

        $this->titleBlock($s, $last, $store, '입 금 내 역', $meta);

        // 요약 카드 (4열)
        $this->summaryRow($s, 5, [
            ['총 발주액', $total, self::DARK],
            ['입금완료', $paid, self::GREEN],
            ['미입금', $unpaid, self::RED],
            ['입금건수', $orders->whereNotNull('paid_at')->count().' / '.$orders->count().' 건', self::MANGO, true],
        ], $last);

        // 표 헤더
        $head = 7;
        $cols = ['발주일', '발주번호', '품목수', '공급가액', '부가세', '택배비', '합계', '입금'];
        foreach ($cols as $i => $label) {
            $s->setCellValue([$i + 1, $head], $label);
        }
        $this->headerRow($s, "A{$head}:{$last}{$head}");

        // 데이터
        $r = $head + 1;
        foreach ($orders as $idx => $o) {
            $amt = (int) $o->store_amount + (int) $o->store_vat + (int) $o->shipping_fee;
            $s->setCellValue([1, $r], optional($o->created_at)->format('Y-m-d'));
            $s->setCellValue([2, $r], $o->order_no);
            $s->setCellValue([3, $r], (int) ($o->items_count ?? 0));
            $s->setCellValue([4, $r], (int) $o->store_amount);
            $s->setCellValue([5, $r], (int) $o->store_vat);
            $s->setCellValue([6, $r], (int) $o->shipping_fee);
            $s->setCellValue([7, $r], $amt);
            $s->setCellValue([8, $r], $o->paid_at ? '입금완료' : '미입금');

            $this->bodyRow($s, $r, $last, $idx);
            // 입금상태 색상
            $s->getStyle("H{$r}")->getFont()->setBold(true)->getColor()->setARGB($o->paid_at ? self::GREEN : self::RED);
            $s->getStyle("H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $r++;
        }
        if ($orders->isEmpty()) {
            $this->emptyRow($s, $r, $last, '해당 기간의 발주 내역이 없습니다.');
            $r++;
        }

        // 합계 행
        $s->setCellValue("A{$r}", '합계');
        $s->mergeCells("A{$r}:C{$r}");
        $s->setCellValue("D{$r}", $orders->sum(fn ($o) => (int) $o->store_amount));
        $s->setCellValue("E{$r}", $orders->sum(fn ($o) => (int) $o->store_vat));
        $s->setCellValue("F{$r}", $orders->sum(fn ($o) => (int) $o->shipping_fee));
        $s->setCellValue("G{$r}", $total);
        $s->setCellValue("H{$r}", number_format($total).'원');
        $this->totalRow($s, $r, $last);

        // 숫자 서식 (공급가액~합계)
        $s->getStyle("C8:G{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $this->finishSheet($s, $last, $head, [14, 18, 9, 14, 12, 12, 15, 12]);
        $s->freezePane("A" . ($head + 1));
    }

    /* ============================ 시트2: 거래내역 ============================ */

    private function buildLedgerSheet(Worksheet $s, Store $store, Collection $entries, array $meta): void
    {
        $s->setTitle('거래내역');
        $s->setShowGridlines(false);
        $last = 'F';

        $this->titleBlock($s, $last, $store, '거 래 내 역 (예치금 원장)', $meta);

        $balance = (int) $store->ledger_balance;
        $this->summaryRow($s, 5, [
            ['현재 잔액', $balance, $balance < 0 ? self::RED : self::GREEN],
            ['정산 유형', $store->settlement_type === 'prepaid' ? '선입금(예치금)' : '후불', self::DARK, true],
            ['가상계좌', $store->virtual_account ?: '-', self::MUTED, true],
            ['거래 건수', $entries->count().' 건', self::MANGO, true],
        ], $last);

        $head = 7;
        $cols = ['일시', '구분', '적요', '증감액', '거래후 잔액', '처리자'];
        foreach ($cols as $i => $label) {
            $s->setCellValue([$i + 1, $head], $label);
        }
        $this->headerRow($s, "A{$head}:{$last}{$head}");

        $r = $head + 1;
        foreach ($entries as $idx => $e) {
            $sign = in_array($e->type, ['charge', 'refund'], true) ? 1 : ($e->type === 'order' ? -1 : 0);
            $s->setCellValue([1, $r], optional($e->created_at)->format('Y-m-d H:i'));
            $s->setCellValue([2, $r], $e->type_label);
            $s->setCellValue([3, $r], $e->memo ?: '-');
            $s->setCellValue([4, $r], (int) $e->amount);
            $s->setCellValue([5, $r], (int) $e->balance_after);
            $s->setCellValue([6, $r], optional($e->creator)->name ?? '시스템');

            $this->bodyRow($s, $r, $last, $idx);
            // 증감액 색상 (충전/환불=녹색, 발주차감=적색)
            $color = $sign > 0 ? self::GREEN : ($sign < 0 ? self::RED : self::MUTED);
            $s->getStyle("D{$r}")->getFont()->setBold(true)->getColor()->setARGB($color);
            $s->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $r++;
        }
        if ($entries->isEmpty()) {
            $this->emptyRow($s, $r, $last, '거래(원장) 내역이 없습니다.');
            $r++;
        }

        $s->getStyle("D8:E{$r}")->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0');
        $this->finishSheet($s, $last, $head, [18, 12, 40, 14, 16, 14]);
        $s->freezePane("A" . ($head + 1));
    }

    /* ============================ 공통 스타일 헬퍼 ============================ */

    /** 상단 타이틀 블록 (1~4행) — 매장 단위 */
    private function titleBlock(Worksheet $s, string $last, Store $store, string $docTitle, array $meta): void
    {
        $period = $meta['period_label'] ?? '전체 기간';
        $subtitle = "매장: {$store->name}" . ($store->region ? " ({$store->region})" : '') . "    |    기간: {$period}    |    발행: " . now()->format('Y-m-d H:i');
        $this->titleBlockText($s, $last, $docTitle, $subtitle);
    }

    /** 상단 타이틀 블록 (1~4행) — 자유 부제 */
    private function titleBlockText(Worksheet $s, string $last, string $docTitle, string $subtitle): void
    {
        $s->mergeCells("A1:{$last}1");
        $s->setCellValue('A1', 'LEEFRIENDS · 리프렌즈 본사');
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB(self::MANGO);
        $s->getRowDimension(1)->setRowHeight(20);

        $s->mergeCells("A2:{$last}2");
        $s->setCellValue('A2', $docTitle);
        $s->getStyle('A2')->getFont()->setBold(true)->setSize(20)->getColor()->setARGB(self::WHITE);
        $s->getStyle("A2:{$last}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::DARK);
        $s->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getRowDimension(2)->setRowHeight(38);

        $s->mergeCells("A3:{$last}3");
        $s->setCellValue('A3', $subtitle);
        $s->getStyle('A3')->getFont()->setSize(10)->getColor()->setARGB(self::MUTED);
        $s->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getRowDimension(3)->setRowHeight(20);
        $s->getRowDimension(4)->setRowHeight(6);
    }

    /** 요약 카드 4개 (라벨/값) — $items = [[label, value, color, isText?], ...] */
    private function summaryRow(Worksheet $s, int $row, array $items, string $last): void
    {
        $labelRow = $row;
        $valueRow = $row + 1;
        $spanCols = (ord($last) - ord('A') + 1);
        $per = max(1, intdiv($spanCols, count($items)));

        $start = 1;
        foreach ($items as $i => [$label, $value, $color]) {
            $isText = $items[$i][3] ?? false;
            $end = ($i === count($items) - 1) ? $spanCols : ($start + $per - 1);
            $c1 = $this->col($start) . $labelRow;
            $c2 = $this->col($end) . $labelRow;
            $v1 = $this->col($start) . $valueRow;
            $v2 = $this->col($end) . $valueRow;

            $s->mergeCells("{$c1}:{$c2}");
            $s->mergeCells("{$v1}:{$v2}");
            $s->setCellValue($c1, $label);
            $s->setCellValue($v1, $isText ? $value : (int) $value);
            if (! $isText) {
                $s->getStyle($v1)->getNumberFormat()->setFormatCode('#,##0"원"');
            }

            $s->getStyle("{$c1}:{$c2}")->getFont()->setBold(true)->setSize(9)->getColor()->setARGB(self::MUTED);
            $s->getStyle("{$c1}:{$c2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::MANGO_SOFT);
            $s->getStyle("{$v1}:{$v2}")->getFont()->setBold(true)->setSize(14)->getColor()->setARGB($color);
            $s->getStyle("{$v1}:{$v2}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFDF5');
            foreach ([$labelRow, $valueRow] as $rr) {
                $s->getStyle($this->col($start) . $rr)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }
            $s->getStyle("{$c1}:{$v2}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFFDE68A');
            $start = $end + 1;
        }
        $s->getRowDimension($labelRow)->setRowHeight(18);
        $s->getRowDimension($valueRow)->setRowHeight(26);
    }

    private function headerRow(Worksheet $s, string $range): void
    {
        $s->getStyle($range)->getFont()->setBold(true)->setSize(10)->getColor()->setARGB(self::WHITE);
        $s->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::DARK);
        $s->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::DARK);
    }

    private function bodyRow(Worksheet $s, int $r, string $last, int $idx): void
    {
        $range = "A{$r}:{$last}{$r}";
        $s->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::LINE);
        $s->getStyle($range)->getFont()->setSize(10);
        if ($idx % 2 === 1) {
            $s->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::ZEBRA);
        }
        $s->getRowDimension($r)->setRowHeight(20);
        // 숫자 열 우측정렬은 setFormatCode 로도 되지만 텍스트열 대비 위해 명시
        $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function totalRow(Worksheet $s, int $r, string $last): void
    {
        $range = "A{$r}:{$last}{$r}";
        $s->getStyle($range)->getFont()->setBold(true)->setSize(11)->getColor()->setARGB(self::WHITE);
        $s->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::MANGO);
        $s->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::MANGO);
        $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getStyle("H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getRowDimension($r)->setRowHeight(26);
    }

    private function emptyRow(Worksheet $s, int $r, string $last, string $msg): void
    {
        $s->mergeCells("A{$r}:{$last}{$r}");
        $s->setCellValue("A{$r}", $msg);
        $s->getStyle("A{$r}")->getFont()->setItalic(true)->getColor()->setARGB(self::MUTED);
        $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getStyle("A{$r}:{$last}{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::LINE);
        $s->getRowDimension($r)->setRowHeight(28);
    }

    /** 열 너비 지정 + 여백 */
    private function finishSheet(Worksheet $s, string $last, int $head, array $widths): void
    {
        foreach ($widths as $i => $w) {
            $s->getColumnDimension($this->col($i + 1))->setWidth($w);
        }
        $s->getSheetView()->setZoomScale(100);
    }

    private function col(int $n): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($n);
    }
}
