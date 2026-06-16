<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InboundController extends Controller
{
    /** 입고예정: 확인된 판매주문 + 배송중 출고 */
    public function index()
    {
        $storeId = Auth::user()->store_id;

        // 입고예정 = 확인됨(confirmed) 판매주문 (아직 출고 전)
        $expected = SalesOrder::where('store_id', $storeId)
            ->where('status', 'confirmed')
            ->with(['supplier', 'order'])
            ->latest()
            ->get();

        // 배송중 = 출고확정된 출고 (송장 포함)
        $inTransit = Shipment::where('store_id', $storeId)
            ->where('status', 'confirmed')
            ->with('supplier')
            ->latest('confirmed_at')
            ->get();

        return view('portal.store.inbound.index', compact('expected', 'inTransit'));
    }
}
