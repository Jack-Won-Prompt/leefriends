<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use App\Models\TaxInvoice;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->role ?: ($user->is_admin ? 'hq' : '');

        if ($role === 'store') {
            return $this->store($user);
        }
        if ($role === 'supplier') {
            return $this->supplier($user);
        }

        return $this->hq();
    }

    private function hq()
    {
        $stats = [
            'orders_pending' => Order::where('status', 'pending')->count(),
            'orders_total' => Order::count(),
            'products' => SupplyProduct::count(),
            'suppliers' => Supplier::count(),
            'stores' => Store::count(),
        ];
        $recentOrders = Order::with('store')->latest()->take(8)->get();
        $newProducts = $this->newProductsToday();

        return view('portal.hq.dashboard', compact('stats', 'recentOrders', 'newProducts'));
    }

    /** 금일 등록된 신규 품목(매장 노출 가능: 활성+승인) — 대시보드 상단 배너용 */
    private function newProductsToday(): array
    {
        $q = SupplyProduct::active()->approved()->whereDate('created_at', today());

        $count = (clone $q)->count();
        $featured = $count > 0
            ? (clone $q)->with('defaultUnit')
                ->orderByRaw("COALESCE(image, '') = ''")   // 이미지 있는 품목 우선
                ->orderByDesc('created_at')->orderByDesc('id')
                ->first()
            : null;

        return ['count' => $count, 'featured' => $featured];
    }

    private function store($user)
    {
        $storeId = $user->store_id;
        $stats = [
            'orders_total' => Order::where('store_id', $storeId)->count(),
            'orders_shipping' => Order::where('store_id', $storeId)->where('status', 'shipping')->count(),
            'orders_completed' => Order::where('store_id', $storeId)->where('status', 'completed')->count(),
        ];
        $recentOrders = Order::where('store_id', $storeId)->latest()->take(8)->get();
        $newProducts = $this->newProductsToday();

        return view('portal.store.dashboard', compact('stats', 'recentOrders', 'user', 'newProducts'));
    }

    private function supplier($user)
    {
        $sid = $user->supplier_id;
        $stats = [
            'pending' => OrderItem::forSupplier($sid)->where('fulfillment_status', 'pending')->count(),
            'shipping' => OrderItem::forSupplier($sid)->where('fulfillment_status', 'shipping')->count(),
            'uninvoiced' => OrderItem::forSupplier($sid)->where('fulfillment_status', 'delivered')->whereNull('tax_invoice_id')->count(),
            'invoices' => TaxInvoice::where('supplier_id', $sid)->count(),
        ];
        $recentItems = OrderItem::forSupplier($sid)->with('order.store')->latest()->take(8)->get();

        return view('portal.supplier.dashboard', compact('stats', 'recentItems', 'user'));
    }
}
