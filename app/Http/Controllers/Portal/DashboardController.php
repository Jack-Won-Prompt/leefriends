<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\Statement;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use App\Models\TaxInvoice;
use Illuminate\Support\Carbon;
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

    /** 금일 등록된 신규 품목(매장 노출 가능: 활성+승인) — 대시보드 상단 배너용 (앱 API 와 공용) */
    private function newProductsToday(): array
    {
        return SupplyProduct::newToday();
    }

    private function store($user)
    {
        $storeId = $user->store_id;
        $monthStart = Carbon::now()->startOfMonth();
        $prevMonthStart = (clone $monthStart)->subMonth();

        // 이번 달 / 지난 달 발주 실적 (배민 홈의 '입금 예정 금액'·'어제 주문' 대응)
        $monthAmount = (int) Order::where('store_id', $storeId)->where('created_at', '>=', $monthStart)->sum('store_amount');
        $monthCount = Order::where('store_id', $storeId)->where('created_at', '>=', $monthStart)->count();
        $prevMonthAmount = (int) Order::where('store_id', $storeId)
            ->whereBetween('created_at', [$prevMonthStart, $monthStart])->sum('store_amount');

        // 처리해야 할 일 (배민 '우리가게NOW' 지표 대응)
        $stats = [
            'orders_active' => Order::where('store_id', $storeId)->whereNotIn('status', ['completed', 'canceled'])->count(),
            'orders_shipping' => Order::where('store_id', $storeId)->where('status', 'shipping')->count(),
            'orders_completed' => Order::where('store_id', $storeId)->where('status', 'completed')->count(),
            'inbound_waiting' => SalesOrder::where('store_id', $storeId)->where('status', 'confirmed')->count()
                + Shipment::where('store_id', $storeId)->where('status', 'confirmed')->count(),
            'statements_unconfirmed' => Statement::where('store_id', $storeId)->whereNull('confirmed_at')->count(),
            'month_amount' => $monthAmount,
            'month_count' => $monthCount,
            'prev_month_amount' => $prevMonthAmount,
        ];

        // 최근 7일 발주 추이 (배민 '주문금액·주문수' 미니차트 대응)
        $since = Carbon::today()->subDays(6);
        $daily = Order::where('store_id', $storeId)->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c, COALESCE(SUM(store_amount),0) as amt')
            ->groupBy('d')->pluck('amt', 'd');
        $dailyCount = Order::where('store_id', $storeId)->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');
        $weekly = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $key = $day->format('Y-m-d');
            $weekly[] = [
                'label' => $day->format('n.j'),
                'dow' => ['일', '월', '화', '수', '목', '금', '토'][$day->dayOfWeek],
                'amount' => (int) ($daily[$key] ?? 0),
                'count' => (int) ($dailyCount[$key] ?? 0),
            ];
        }

        $notices = Notice::published()->orderByDesc('is_pinned')->orderByDesc('published_at')->take(3)->get();
        $recentOrders = Order::where('store_id', $storeId)->latest()->take(6)->get();
        $newProducts = $this->newProductsToday();

        return view('portal.store.dashboard', compact('stats', 'weekly', 'notices', 'recentOrders', 'user', 'newProducts'));
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
