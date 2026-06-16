<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceivingController extends Controller
{
    public function show(Shipment $shipment)
    {
        abort_unless($shipment->store_id === Auth::user()->store_id, 403);
        $shipment->load(['items', 'supplier']);

        return view('portal.store.inbound.show', compact('shipment'));
    }

    /** 거래명세서 바코드 스캔/확인 → 인수확인 + 입고완료 (재고 가산) */
    public function receive(Shipment $shipment, InventoryService $inventory)
    {
        abort_unless($shipment->store_id === Auth::user()->store_id, 403);

        $inventory->receiveShipment($shipment, Auth::id());

        return redirect()->route('portal.store.inventory.index')
            ->with('success', '입고가 완료되었습니다. 재고에 반영되었습니다.');
    }
}
