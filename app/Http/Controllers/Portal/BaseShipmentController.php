<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\OrderChange;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\Store;
use App\Services\Fulfillment\ShipmentService;
use Illuminate\Http\Request;

/**
 * 본사/공급처 공통 출고 처리. 서브클래스가 seller()/viewPrefix()/routePrefix() 제공.
 */
abstract class BaseShipmentController extends Controller
{
    /** @return array{0:string,1:?int} [seller_type, supplier_id] */
    abstract protected function seller(): array;

    abstract protected function viewPrefix(): string;   // 예: portal.hq

    abstract protected function routePrefix(): string;  // 예: portal.hq

    public function index(Request $request)
    {
        [$type, $sid] = $this->seller();
        $status = $request->query('status', 'all');
        $store = $request->query('store', 'all');

        $query = Shipment::forSeller($type, $sid)->with('store')->latest();
        if (array_key_exists($status, Shipment::STATUSES)) {
            $query->where('status', $status);
        }
        if ($store !== 'all') {
            $query->where('store_id', $store);
        }
        $shipments = $query->paginate(15)->withQueryString();

        return view('portal.shared.shipments.index', [
            'shipments' => $shipments,
            'status' => $status,
            'statuses' => Shipment::STATUSES,
            'routePrefix' => $this->routePrefix(),
            'stores' => Store::whereIn('id', Shipment::forSeller($type, $sid)->distinct()->pluck('store_id'))->orderBy('name')->get(),
            'store' => $store,
        ]);
    }

    public function create()
    {
        [$type, $sid] = $this->seller();

        // 확인된 판매주문 + 미출고 품목, 매장별 그룹
        $items = OrderItem::whereNull('shipment_id')
            ->whereHas('salesOrder', fn ($q) => $q
                ->forSeller($type, $sid)->where('status', 'confirmed'))
            ->with(['order.store', 'salesOrder'])
            ->get()
            ->groupBy(fn ($i) => $i->order->store_id);

        $stores = Store::whereIn('id', $items->keys())->get()->keyBy('id');

        return view('portal.shared.shipments.create', [
            'grouped' => $items,
            'stores' => $stores,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function store(Request $request, ShipmentService $service)
    {
        [$type, $sid] = $this->seller();

        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer'],
            'note' => ['nullable', 'string', 'max:500'],
        ], ['items.required' => '출고할 품목을 선택해 주세요.']);

        // 확인 게이트: 대상 품목 주문에 미반영 매장 변경이 있으면 차단
        $orderIds = OrderItem::whereIn('id', $data['items'])->distinct()->pluck('order_id');
        if (OrderChange::forSeller($type, $sid)->pending()->whereIn('order_id', $orderIds)->exists()) {
            return back()->with('error', '선택한 주문에 미반영된 매장 변경이 있습니다. «매장 주문 변경»에서 확인(반영) 후 출고하세요.');
        }

        $shipment = $service->create($type, $sid, (int) $data['store_id'], $data['items'], $data['note'] ?? null);

        return redirect()->route($this->routePrefix() . '.shipments.show', $shipment)
            ->with('success', '출고가 생성되었습니다. 송장 입력 후 출고확정하세요.');
    }

    public function show(Shipment $shipment)
    {
        $this->authorizeShipment($shipment);
        $shipment->load(['store', 'items']);

        return view('portal.shared.shipments.show', [
            'shipment' => $shipment,
            'routePrefix' => $this->routePrefix(),
            'couriers' => Courier::active()->ordered()->get(),
        ]);
    }

    public function confirm(Request $request, Shipment $shipment, ShipmentService $service)
    {
        $this->authorizeShipment($shipment);

        $data = $request->validate([
            'carrier' => ['required', 'string', 'max:50'],
            'tracking_no' => ['nullable', 'string', 'max:50'],
        ], [
            'carrier.required' => '택배사를 선택해 주세요.',
        ]);

        // 직접 배송이면 송장번호 불필요, 그 외에는 필수
        $isDirect = Courier::where('name', $data['carrier'])->where('is_direct', true)->exists();
        if (! $isDirect && empty($data['tracking_no'])) {
            return back()->withErrors(['tracking_no' => '송장번호를 입력해 주세요.'])->withInput();
        }

        $service->confirm($shipment, $data['carrier'], $data['tracking_no'] ?? '');

        return back()->with('success', '출고가 확정되었습니다. 매장에 배송시작 알림(FCM)을 전송했습니다.');
    }

    public function statement(Shipment $shipment)
    {
        $this->authorizeShipment($shipment);
        $shipment->load(['store', 'items', 'supplier']);

        return view('portal.shipments.statement', ['shipment' => $shipment]);
    }

    protected function authorizeShipment(Shipment $shipment): void
    {
        [$type, $sid] = $this->seller();
        abort_unless($shipment->seller_type === $type && $shipment->supplier_id == $sid, 403);
    }
}
