<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreLedgerEntry;
use App\Services\Settlement\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** 본사 — 매장 거래 원장 (예치금 잔액 · 미수금 · 충전/조정) */
class StoreLedgerController extends Controller
{
    /** 매장별 잔액 목록 */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $stores = Store::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')->paginate(20)->withQueryString();

        $totals = [
            'prepaid' => (int) Store::where('ledger_balance', '>', 0)->sum('ledger_balance'),
            'unpaid' => (int) abs(Store::where('ledger_balance', '<', 0)->sum('ledger_balance')),
        ];

        return view('portal.hq.store_ledger.index', compact('stores', 'q', 'totals'));
    }

    /** 매장 원장 상세 (타임라인) */
    public function show(Store $store)
    {
        $entries = $store->ledgerEntries()->with('creator')->paginate(30);

        return view('portal.hq.store_ledger.show', compact('store', 'entries'));
    }

    /** 수동 충전 */
    public function charge(Request $request, Store $store, LedgerService $ledger)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'memo' => ['nullable', 'string', 'max:200'],
        ]);
        $ledger->manualCharge($store, (int) $data['amount'], $data['memo'] ?? '수동 충전', Auth::id());

        return back()->with('success', number_format($data['amount']).'원을 충전했습니다.');
    }

    /** 잔액 조정 (목표 잔액) */
    public function adjust(Request $request, Store $store, LedgerService $ledger)
    {
        $data = $request->validate([
            'balance' => ['required', 'integer'],
            'memo' => ['nullable', 'string', 'max:200'],
        ]);
        $ledger->adjust($store, (int) $data['balance'], $data['memo'] ?? '잔액 조정', Auth::id());

        return back()->with('success', '잔액을 조정했습니다.');
    }

    /** 정산 설정 (선불/후불, 가상계좌) */
    public function settings(Request $request, Store $store)
    {
        $data = $request->validate([
            'settlement_type' => ['required', 'in:prepaid,postpaid'],
            'virtual_account' => ['nullable', 'string', 'max:100'],
        ]);
        $store->update($data);

        return back()->with('success', '정산 설정을 저장했습니다.');
    }
}
