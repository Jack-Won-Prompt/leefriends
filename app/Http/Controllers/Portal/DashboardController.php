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
        $monthStart = Carbon::now()->startOfMonth();
        $stats = [
            'orders_pending' => Order::where('status', 'pending')->count(),
            'orders_shipping' => Order::where('status', 'shipping')->count(),
            'orders_completed' => Order::where('status', 'completed')->count(),
            'orders_total' => Order::count(),
            'products' => SupplyProduct::count(),
            'suppliers' => Supplier::count(),
            'stores' => Store::count(),
            'month_amount' => (int) Order::where('created_at', '>=', $monthStart)->sum('store_amount'),
            'month_count' => Order::where('created_at', '>=', $monthStart)->count(),
        ];
        $weekly = $this->weeklyOrderSeries();
        $notices = Notice::published()->orderByDesc('is_pinned')->orderByDesc('published_at')->take(3)->get();
        $recentOrders = Order::with('store')->latest()->take(6)->get();
        $newProducts = $this->newProductsToday();

        return view('portal.hq.dashboard', compact('stats', 'weekly', 'notices', 'recentOrders', 'newProducts'));
    }

    /** 금일 등록된 신규 품목(매장 노출 가능: 활성+승인) — 대시보드 상단 배너용 (앱 API 와 공용) */
    private function newProductsToday(): array
    {
        return SupplyProduct::newToday();
    }

    /**
     * 최근 7일 발주(금액·건수) 시계열 — 대시보드 미니차트 공용.
     * $storeId 지정 시 해당 매장, null 이면 전체(본사).
     */
    private function weeklyOrderSeries($storeId = null): array
    {
        $since = Carbon::today()->subDays(6);
        $base = Order::where('created_at', '>=', $since);
        if ($storeId) {
            $base->where('store_id', $storeId);
        }
        $amt = (clone $base)->selectRaw('DATE(created_at) as d, COALESCE(SUM(store_amount),0) as amt')->groupBy('d')->pluck('amt', 'd');
        $cnt = (clone $base)->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');

        $dows = ['일', '월', '화', '수', '목', '금', '토'];
        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $key = $day->format('Y-m-d');
            $out[] = [
                'label' => $day->format('n.j'),
                'dow' => $dows[$day->dayOfWeek],
                'amount' => (int) ($amt[$key] ?? 0),
                'count' => (int) ($cnt[$key] ?? 0),
            ];
        }

        return $out;
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
        $weekly = $this->weeklyOrderSeries($storeId);

        $notices = Notice::published()->orderByDesc('is_pinned')->orderByDesc('published_at')->take(3)->get();
        $recentOrders = Order::where('store_id', $storeId)->latest()->take(6)->get();
        $newProducts = $this->newProductsToday();

        return view('portal.store.dashboard', compact('stats', 'weekly', 'notices', 'recentOrders', 'user', 'newProducts'));
    }

    private function supplier($user)
    {
        $sid = $user->supplier_id;
        $monthStart = Carbon::now()->startOfMonth();
        $stats = [
            'pending' => OrderItem::forSupplier($sid)->where('fulfillment_status', 'pending')->count(),
            'shipping' => OrderItem::forSupplier($sid)->where('fulfillment_status', 'shipping')->count(),
            'delivered' => OrderItem::forSupplier($sid)->where('fulfillment_status', 'delivered')->count(),
            'uninvoiced' => OrderItem::forSupplier($sid)->where('fulfillment_status', 'delivered')->whereNull('tax_invoice_id')->count(),
            'invoices' => TaxInvoice::where('supplier_id', $sid)->count(),
            'month_count' => OrderItem::forSupplier($sid)->where('created_at', '>=', $monthStart)->count(),
        ];

        // 최근 7일 출고 요청(주문 품목) 추이 — 건수 기준
        $since = Carbon::today()->subDays(6);
        $cnt = OrderItem::forSupplier($sid)->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');
        $dows = ['일', '월', '화', '수', '목', '금', '토'];
        $weekly = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $weekly[] = [
                'label' => $day->format('n.j'),
                'dow' => $dows[$day->dayOfWeek],
                'count' => (int) ($cnt[$day->format('Y-m-d')] ?? 0),
                'amount' => 0,
            ];
        }

        $notices = Notice::published()->orderByDesc('is_pinned')->orderByDesc('published_at')->take(3)->get();
        $recentItems = OrderItem::forSupplier($sid)->with('order.store')->latest()->take(6)->get();

        return view('portal.supplier.dashboard', compact('stats', 'weekly', 'notices', 'recentItems', 'user'));
    }
}
