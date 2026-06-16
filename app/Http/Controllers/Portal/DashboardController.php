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

        return view('portal.hq.dashboard', compact('stats', 'recentOrders'));
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

        return view('portal.store.dashboard', compact('stats', 'recentOrders', 'user'));
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
