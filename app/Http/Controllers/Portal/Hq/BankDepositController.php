<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\BankCollectJob;
use App\Models\BankDeposit;
use App\Models\BankDepositorMapping;
use App\Models\Order;
use App\Models\Store;
use App\Services\Popbill\PopbillEasyFinBankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 본사 계좌연동 입금확인 — 계좌 거래내역 수집 + 입금자↔매장 매핑 + 주문 대사.
 */
class BankDepositController extends Controller
{
    public function __construct(private PopbillEasyFinBankService $bank)
    {
    }

    private function corpNum(): string
    {
        return preg_replace('/\D/', '', (string) config('popbill.hq.corp_num'));
    }

    public function index(Request $request)
    {
        $corp = $this->corpNum();

        // 등록된 계좌 목록 (팝빌 콘솔에서 등록)
        $accounts = [];
        $accountsError = null;
        try {
            $accounts = $this->bank->listBankAccount($corp);
        } catch (\Throwable $e) {
            $accountsError = $e->getMessage();
        }

        // 선택 계좌 (bankCode|accountNumber)
        $selAcc = $request->query('acc');
        $bankCode = $accountNumber = null;
        if ($selAcc && str_contains($selAcc, '|')) {
            [$bankCode, $accountNumber] = explode('|', $selAcc, 2);
        } elseif (! empty($accounts)) {
            $bankCode = $accounts[0]->bankCode;
            $accountNumber = $accounts[0]->accountNumber;
        }

        $jobs = collect();
        $selected = null;
        $deposits = collect();
        if ($bankCode && $accountNumber) {
            $jobs = BankCollectJob::where('corp_num', $corp)
                ->where('bank_code', $bankCode)->where('account_number', $accountNumber)
                ->orderByDesc('id')->limit(15)->get();

            if ($jid = $request->query('job_id')) {
                $selected = $jobs->firstWhere('job_id', $jid);
            }
            $selected ??= $jobs->firstWhere('job_state', 3);

            // 완료됐고 아직 미반영이면 입금내역 로컬 적재
            if ($selected && $selected->isDone() && ! $selected->imported_at) {
                $this->importDeposits($corp, $selected);
            }

            $q = BankDeposit::where('corp_num', $corp)
                ->where('bank_code', $bankCode)->where('account_number', $accountNumber)
                ->where('acc_in', '>', 0)
                ->with('matchedOrder.store');
            if ($selected) {
                $q->whereBetween('trade_date', [$selected->start_date, $selected->end_date]);
            }
            $deposits = $q->orderByDesc('trade_date')->orderByDesc('id')->limit(300)->get();
        }

        // 입금자 → 매장 매핑, 미매칭 입금건 후보주문
        $map = BankDepositorMapping::mapFor($corp);
        $stores = Store::orderBy('name')->get(['id', 'name']);
        $storeById = $stores->keyBy('id');

        // 후보 주문: 매핑된 매장의 미입금 주문 (금액 일치)
        $resolvedStore = [];
        $candidates = [];
        $needStoreIds = [];
        foreach ($deposits as $d) {
            $sid = $map[BankDepositorMapping::normalize($d->depositor)] ?? null;
            $resolvedStore[$d->id] = $sid;
            if ($sid && ! $d->isMatched()) {
                $needStoreIds[$sid] = true;
            }
        }
        if ($needStoreIds) {
            $unpaid = Order::whereIn('store_id', array_keys($needStoreIds))
                ->whereNull('paid_at')
                ->get(['id', 'order_no', 'store_id', 'store_amount', 'store_vat', 'shipping_fee', 'created_at']);
            foreach ($deposits as $d) {
                $sid = $resolvedStore[$d->id];
                if (! $sid || $d->isMatched()) {
                    continue;
                }
                $candidates[$d->id] = $unpaid
                    ->where('store_id', $sid)
                    ->filter(fn ($o) => $o->order_total === (int) $d->acc_in)
                    ->values();
            }
        }

        $summary = [
            'total' => $deposits->sum('acc_in'),
            'matched' => $deposits->filter->isMatched()->count(),
            'unmatched' => $deposits->reject->isMatched()->count(),
            'count' => $deposits->count(),
        ];

        return view('portal.hq.bank.index', [
            'corp' => $corp,
            'accounts' => $accounts,
            'accountsError' => $accountsError,
            'bankCode' => $bankCode,
            'accountNumber' => $accountNumber,
            'selAcc' => $bankCode && $accountNumber ? "$bankCode|$accountNumber" : null,
            'jobs' => $jobs,
            'selected' => $selected,
            'deposits' => $deposits,
            'resolvedStore' => $resolvedStore,
            'candidates' => $candidates,
            'stores' => $stores,
            'storeById' => $storeById,
            'summary' => $summary,
            'defStart' => now()->startOfMonth()->format('Y-m-d'),
            'defEnd' => now()->format('Y-m-d'),
        ]);
    }

    /** 수집 요청 */
    public function requestJob(Request $request)
    {
        $data = $request->validate([
            'acc' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'acc.required' => '계좌를 선택해 주세요.',
            'end_date.after_or_equal' => '종료일은 시작일 이후여야 합니다.',
        ]);

        [$bankCode, $accountNumber] = explode('|', $data['acc'], 2);
        $corp = $this->corpNum();
        $sDate = str_replace('-', '', $data['start_date']);
        $eDate = str_replace('-', '', $data['end_date']);

        try {
            $jobId = $this->bank->requestJob($corp, $bankCode, $accountNumber, $sDate, $eDate);
        } catch (\Throwable $e) {
            return back()->with('error', '수집 요청 실패: '.$e->getMessage());
        }

        BankCollectJob::create([
            'corp_num' => $corp,
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'start_date' => $sDate,
            'end_date' => $eDate,
            'job_id' => $jobId,
            'job_state' => 1,
            'requested_by' => Auth::id(),
        ]);

        return redirect()
            ->route('portal.hq.bank.index', ['acc' => $data['acc'], 'job_id' => $jobId])
            ->with('success', '수집을 요청했습니다. 잠시 후 완료됩니다.');
    }

    /** 수집 상태 폴링 (AJAX) */
    public function jobState(BankCollectJob $job)
    {
        try {
            $state = $this->bank->getJobState($job->corp_num, $job->job_id);
            $job->update([
                'job_state' => (int) ($state->jobState ?? $job->job_state),
                'error_code' => isset($state->errorCode) && $state->errorCode !== '' ? (int) $state->errorCode : null,
                'error_reason' => $state->errorReason ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 200);
        }

        return response()->json([
            'ok' => true,
            'state' => $job->job_state,
            'label' => $job->stateLabel(),
            'done' => $job->isDone(),
        ]);
    }

    /** Search 결과를 bank_deposits 로 적재 (입금건만) */
    private function importDeposits(string $corp, BankCollectJob $job): void
    {
        $count = 0;
        $page = 1;
        do {
            try {
                $res = $this->bank->search($corp, $job->job_id, ['I'], null, $page, 100, 'D');
            } catch (\Throwable $e) {
                break;
            }
            foreach (($res->list ?? []) as $r) {
                if (empty($r->tid)) {
                    continue;
                }
                BankDeposit::updateOrCreate(
                    ['tid' => $r->tid],
                    [
                        'corp_num' => $corp,
                        'bank_code' => $job->bank_code,
                        'account_number' => $job->account_number,
                        'trade_date' => $r->trdate ?? '',
                        'trade_dt' => $r->trdt ?? null,
                        'acc_in' => (int) ($r->accIn ?? 0),
                        'acc_out' => (int) ($r->accOut ?? 0),
                        'balance' => isset($r->balance) ? (int) $r->balance : null,
                        'depositor' => $r->remark1 ?? null,
                        'remark' => trim(implode(' ', array_filter([$r->remark2 ?? null, $r->remark3 ?? null, $r->remark4 ?? null]))) ?: null,
                    ]
                );
                $count++;
            }
            $totalPages = (int) ($res->pageCount ?? 1);
            $page++;
        } while ($page <= $totalPages && $page <= 20);

        $job->update(['imported_at' => now(), 'collect_count' => $count]);
    }

    /** 입금자명 ↔ 매장 매핑 저장 (이후 동일 입금자는 자동 인식) */
    public function mapDepositor(Request $request)
    {
        $data = $request->validate([
            'depositor_name' => ['required', 'string', 'max:120'],
            'store_id' => ['required', 'exists:stores,id'],
            'acc' => ['nullable', 'string'],
        ]);

        BankDepositorMapping::updateOrCreate(
            ['corp_num' => $this->corpNum(), 'depositor_name' => $data['depositor_name']],
            ['store_id' => $data['store_id']]
        );

        return back()->with('success', "입금자 '{$data['depositor_name']}' → 매장 매핑을 저장했습니다.");
    }

    /** 여러 입금자 → 한 매장 일괄 매핑 */
    public function mapDepositorBulk(Request $request)
    {
        $data = $request->validate([
            'depositor_names' => ['required', 'array', 'min:1'],
            'depositor_names.*' => ['string', 'max:120'],
            'store_id' => ['required', 'exists:stores,id'],
        ]);

        $corp = $this->corpNum();
        $names = array_values(array_unique(array_filter(array_map('trim', $data['depositor_names']))));
        foreach ($names as $name) {
            BankDepositorMapping::updateOrCreate(
                ['corp_num' => $corp, 'depositor_name' => $name],
                ['store_id' => $data['store_id']]
            );
        }

        return back()->with('success', count($names).'명의 입금자를 한 매장으로 매핑했습니다.');
    }

    /** 입금건 ↔ 주문 대사 확정 */
    public function match(Request $request)
    {
        $data = $request->validate([
            'deposit_id' => ['required', 'exists:bank_deposits,id'],
            'order_id' => ['required', 'exists:orders,id'],
        ]);

        DB::transaction(function () use ($data) {
            $deposit = BankDeposit::lockForUpdate()->findOrFail($data['deposit_id']);
            $order = Order::lockForUpdate()->findOrFail($data['order_id']);
            $deposit->update(['matched_order_id' => $order->id, 'confirmed_at' => now()]);
            $order->update(['paid_at' => now()]);
        });

        return back()->with('success', '입금을 주문과 대사했습니다.');
    }

    /** 대사 해제 */
    public function unmatch(BankDeposit $deposit)
    {
        DB::transaction(function () use ($deposit) {
            if ($deposit->matched_order_id) {
                Order::where('id', $deposit->matched_order_id)->update(['paid_at' => null]);
            }
            $deposit->update(['matched_order_id' => null, 'confirmed_at' => null]);
        });

        return back()->with('success', '대사를 해제했습니다.');
    }

    /** 매핑된 매장 기준, 금액이 유일하게 일치하는 미입금 주문 자동 대사 */
    public function autoMatch(Request $request)
    {
        $corp = $this->corpNum();
        $map = BankDepositorMapping::mapFor($corp);
        $q = BankDeposit::where('corp_num', $corp)->where('acc_in', '>', 0)->whereNull('matched_order_id');
        if ($request->filled('acc') && str_contains($request->input('acc'), '|')) {
            [$bc, $an] = explode('|', $request->input('acc'), 2);
            $q->where('bank_code', $bc)->where('account_number', $an);
        }
        $deposits = $q->get();

        $matched = 0;
        foreach ($deposits as $d) {
            $sid = $map[BankDepositorMapping::normalize($d->depositor)] ?? null;
            if (! $sid) {
                continue;
            }
            $orders = Order::where('store_id', $sid)->whereNull('paid_at')
                ->get(['id', 'store_amount', 'store_vat', 'shipping_fee'])
                ->filter(fn ($o) => $o->order_total === (int) $d->acc_in)
                ->values();
            if ($orders->count() === 1) {
                DB::transaction(function () use ($d, $orders) {
                    $d->update(['matched_order_id' => $orders[0]->id, 'confirmed_at' => now()]);
                    Order::where('id', $orders[0]->id)->update(['paid_at' => now()]);
                });
                $matched++;
            }
        }

        return back()->with('success', "자동 대사 {$matched}건을 처리했습니다.");
    }

    /** 정액제 신청 팝업 */
    public function flatRateUrl()
    {
        try {
            return redirect()->away($this->bank->getFlatRatePopUpURL($this->corpNum()));
        } catch (\Throwable $e) {
            return back()->with('error', '정액제 신청 URL 발급 실패: '.$e->getMessage());
        }
    }
}
