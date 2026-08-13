<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Portal\BaseShipmentController;
use App\Models\Order;
use App\Models\Store;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;

class ShipmentController extends BaseShipmentController
{
    protected function seller(): array
    {
        return ['hq', null];
    }

    protected function viewPrefix(): string
    {
        return 'portal.hq';
    }

    protected function routePrefix(): string
    {
        return 'portal.hq';
    }

    /**
     * 출고 화면 = 매장 발주 등록 건 전체 조회(미출고/출고 무관) + 날짜 필터.
     * 체크한 발주를 «출고지시서»(매장별·QR)로 인쇄한다.
     */
    public function index(Request $request)
    {
        $store = $request->query('store', 'all');
        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $query = Order::with('store')
            ->withCount('items')
            ->withExists(['items as has_pending' => fn ($q) => $q->where('price_pending', true)])
            ->latest();

        if ($store !== 'all') {
            $query->where('store_id', $store);
        }
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        $orders = $query->paginate(30)->withQueryString();

        return view('portal.hq.shipments.index', [
            'orders' => $orders,
            'stores' => Store::orderBy('name')->get(),
            'store' => $store,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * 선택한 발주들을 매장별 출고지시서(브라우저 인쇄용)로 출력.
     * 각 발주번호를 QR 로 인코딩해 스캔 가능하게 한다.
     * 미확인 단가(싯가) 품목이 포함된 발주가 있으면 출력을 차단한다.
     */
    public function pickingSlip(Request $request)
    {
        $ids = array_filter(array_map('intval', (array) $request->query('orders', [])));
        abort_if(empty($ids), 404, '선택된 발주가 없습니다.');

        $orders = Order::with(['items', 'store', 'user'])
            ->whereIn('id', $ids)
            ->orderBy('store_id')
            ->orderBy('id')
            ->get();
        abort_if($orders->isEmpty(), 404, '선택된 발주가 없습니다.');

        $pending = $orders->filter->hasPendingPrice();
        abort_if(
            $pending->isNotEmpty(),
            422,
            '미확인 단가(싯가) 품목이 포함된 발주가 있어 출고지시서를 출력할 수 없습니다. 단가 확정 후 다시 시도하세요. ('.$pending->pluck('order_no')->join(', ').')'
        );

        $grouped = $orders->groupBy('store_id');
        $qr = [];
        foreach ($orders as $o) {
            $qr[$o->id] = $this->qrSvg($o->order_no);
        }

        return view('portal.print.picking-slip', [
            'grouped' => $grouped,
            'qr' => $qr,
            'printedAt' => now(),
        ]);
    }

    /** 발주번호 → 스캔 가능한 QR SVG 문자열 (라이브러리 미설치 시 텍스트로 대체) */
    private function qrSvg(string $text): string
    {
        if (! class_exists(Writer::class)) {
            return '<div style="font:11px monospace;color:#6b7280;text-align:center;padding:8px;">'.e($text).'</div>';
        }

        $renderer = new ImageRenderer(
            new RendererStyle(150, 1),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($text);
    }
}
