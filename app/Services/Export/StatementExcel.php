<?php

namespace App\Services\Export;

use App\Models\Statement;
use App\Models\SupplyProduct;
use App\Support\TaxSummary;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * 거래명세서(본사→매장) 1건을 고급 스타일 .xlsx 로 생성.
 * StoreLedgerExcel 과 동일한 브랜드 스타일.
 */
class StatementExcel
{
    private const DARK = 'FF1F2937';
    private const MANGO = 'FFF59E0B';
    private const MANGO_SOFT = 'FFFFF3C4';
    private const LINE = 'FFE5E7EB';
    private const ZEBRA = 'FFF9FAFB';
    private const SKY = 'FF0369A1';
    private const WHITE = 'FFFFFFFF';
    private const MUTED = 'FF6B7280';
    private const LAST = 'F';

    public function build(Statement $statement): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('LEEFRIENDS 본사')
            ->setTitle("거래명세서 {$statement->store_name}")
            ->setCompany('주식회사 오다네트웍스');

        $s = $book->getActiveSheet();
        $s->setTitle('거래명세서');
        $s->setShowGridlines(false);

        $items = $statement->items ?? [];
        $tax = TaxSummary::fromLines($items);

        $this->titleBlock($s, $statement);

        // 데이터 행
        $head = 7;
        foreach (['품목', '구분', '단가', '수량', '공급가액', '부가세'] as $i => $label) {
            $s->setCellValue([$i + 1, $head], $label);
        }
        $this->headerRow($s, "A{$head}:" . self::LAST . $head);

        $r = $head + 1;
        foreach ($items as $idx => $l) {
            $tt = $l['tax_type'] ?? 'inc';
            [$sup, $ltax] = SupplyProduct::taxBreakdown($tt, (int) ($l['amount'] ?? 0));
            $name = ($l['name'] ?? '-') . (! empty($l['code']) ? "  ({$l['code']})" : '');
            $s->setCellValue([1, $r], $name);
            $s->setCellValue([2, $r], $tt === 'exempt' ? '면세' : '과세');
            $s->setCellValue([3, $r], (int) ($l['price'] ?? 0));
            $s->setCellValue([4, $r], (int) ($l['qty'] ?? 0) . ($l['unit'] ?? ''));
            $s->setCellValue([5, $r], $sup);
            $s->setCellValue([6, $r], $tt === 'exempt' ? 0 : $ltax);

            $this->bodyRow($s, $r, $idx);
            $s->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $s->getStyle("B{$r}")->getFont()->getColor()->setARGB($tt === 'exempt' ? self::SKY : self::MUTED);
            $s->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $r++;
        }
        if (empty($items)) {
            $s->mergeCells("A{$r}:" . self::LAST . $r);
            $s->setCellValue("A{$r}", '품목 내역이 없습니다.');
            $s->getStyle("A{$r}")->getFont()->setItalic(true)->getColor()->setARGB(self::MUTED);
            $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $s->getStyle("A{$r}:" . self::LAST . $r)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::LINE);
            $r++;
        }

        // 합계 요약 (과세공급가액 / 부가세 / 면세 / 합계)
        $r++;
        $this->summaryLine($s, $r++, '과세 공급가액', $tax['taxable']);
        $this->summaryLine($s, $r++, '부가세 (VAT)', $tax['vat']);
        if (($tax['exempt'] ?? 0) > 0) {
            $this->summaryLine($s, $r++, '면세 공급가액', $tax['exempt']);
        }
        $this->totalLine($s, $r, '합계금액 (부가세 포함)', (int) $statement->total);

        $s->getStyle("C" . ($head + 1) . ":F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        foreach ([38, 10, 14, 10, 16, 14] as $i => $w) {
            $s->getColumnDimension($this->col($i + 1))->setWidth($w);
        }
        $s->freezePane('A' . ($head + 1));

        return $book;
    }

    private function titleBlock(Worksheet $s, Statement $statement): void
    {
        $L = self::LAST;
        $s->mergeCells("A1:{$L}1");
        $s->setCellValue('A1', 'LEEFRIENDS · 리프렌즈 본사');
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB(self::MANGO);
        $s->getRowDimension(1)->setRowHeight(20);

        $s->mergeCells("A2:{$L}2");
        $s->setCellValue('A2', '거 래 명 세 서');
        $s->getStyle('A2')->getFont()->setBold(true)->setSize(20)->getColor()->setARGB(self::WHITE);
        $s->getStyle("A2:{$L}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::DARK);
        $s->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getRowDimension(2)->setRowHeight(38);

        $s->mergeCells("A3:{$L}3");
        $s->setCellValue('A3', '공급자: 주식회사 오다네트웍스(본사)    |    공급받는자: ' . $statement->store_name);
        $s->getStyle('A3')->getFont()->setSize(10)->getColor()->setARGB(self::MUTED);
        $s->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getRowDimension(3)->setRowHeight(20);

        $s->mergeCells("A4:{$L}4");
        $issue = optional($statement->issueDate())->format('Y-m-d');
        $sent = optional($statement->sent_at)->format('Y-m-d H:i');
        $s->setCellValue('A4', "발행일자: {$issue}    |    발송일시: {$sent}    |    수신: {$statement->email}");
        $s->getStyle('A4')->getFont()->setSize(10)->getColor()->setARGB(self::MUTED);
        $s->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getRowDimension(4)->setRowHeight(20);
        $s->getRowDimension(6)->setRowHeight(6);
    }

    private function headerRow(Worksheet $s, string $range): void
    {
        $s->getStyle($range)->getFont()->setBold(true)->setSize(10)->getColor()->setARGB(self::WHITE);
        $s->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::DARK);
        $s->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::DARK);
    }

    private function bodyRow(Worksheet $s, int $r, int $idx): void
    {
        $range = "A{$r}:" . self::LAST . $r;
        $s->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::LINE);
        $s->getStyle($range)->getFont()->setSize(10);
        if ($idx % 2 === 1) {
            $s->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::ZEBRA);
        }
        $s->getRowDimension($r)->setRowHeight(20);
    }

    private function summaryLine(Worksheet $s, int $r, string $label, int $value): void
    {
        $s->mergeCells("A{$r}:D{$r}");
        $s->setCellValue("A{$r}", $label);
        $s->setCellValue("E{$r}", $value);
        $s->mergeCells("E{$r}:F{$r}");
        $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $s->getStyle("E{$r}")->getNumberFormat()->setFormatCode('#,##0"원"');
        $s->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $s->getStyle("A{$r}:F{$r}")->getFont()->setSize(10)->getColor()->setARGB(self::DARK);
        $s->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::MANGO_SOFT);
        $s->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFFDE68A');
        $s->getRowDimension($r)->setRowHeight(20);
    }

    private function totalLine(Worksheet $s, int $r, string $label, int $value): void
    {
        $s->mergeCells("A{$r}:D{$r}");
        $s->setCellValue("A{$r}", $label);
        $s->mergeCells("E{$r}:F{$r}");
        $s->setCellValue("E{$r}", $value);
        $s->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $s->getStyle("E{$r}")->getNumberFormat()->setFormatCode('#,##0"원"');
        $s->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $s->getStyle("A{$r}:F{$r}")->getFont()->setBold(true)->setSize(13)->getColor()->setARGB(self::WHITE);
        $s->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::MANGO);
        $s->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB(self::MANGO);
        $s->getRowDimension($r)->setRowHeight(28);
    }

    private function col(int $n): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($n);
    }
}
