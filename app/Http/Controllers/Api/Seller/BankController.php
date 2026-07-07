<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\BankCollectJob;
use App\Models\BankDeposit;
use App\Models\BankDepositorMapping;
use App\Models\Order;
use App\Models\Store;
use App\Services\Popbill\PopbillEasyFinBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 모바일 앱 — 본사 계좌연동 입금확인(계좌 거래내역 수집 + 입금자↔매장 매핑 + 주문 대사).
 * 웹 Portal\Hq\BankDepositController 와 동일 로직. 본사(hq) 전용.
 */
class BankController extends Controller
{
    use ResolvesSeller;

    public function __construct(private PopbillEasyFinBankService $bank)
    {
    }

    private function corpNum(): string
    {
        return preg_replace('/\D/', '', (string) config('popbill.hq.corp_num'));
    }

    private function guardHq(Request $request): void
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->guardHq($request);
        $corp = $this->corpNum();

        $accounts = [];
        $accountsError = null;
        try {
            $accounts = $this->bank->listBankAccount($corp);
        } catch (\Throwable $e) {
            $accountsError = $e->getMessage();
        }

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

        $map = BankDepositorMapping::mapFor($corp);
        $stores = Store::orderBy('name')->get(['id', 'name']);
        $storeById = $stores->keyBy('id');

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
        $unpaid = collect();
        if ($needStoreIds) {
            $unpaid = Order::whereIn('store_id', array_keys($needStoreIds))
                ->whereNull('paid_at')
                ->get(['id', 'order_no', 'store_id', 'store_amount', 'shipping_fee', 'created_at']);
        }

        $depositRows = $deposits->map(function (BankDeposit $d) use ($resolvedStore, $storeById, $unpaid) {
            $sid = $resolvedStore[$d->id] ?? null;
            $cands = [];
            if ($sid && ! $d->isMatched()) {
                $cands = $unpaid->where('store_id', $sid)
                    ->filter(fn ($o) => $o->order_total === (int) $d->acc_in)
                    ->map(fn ($o) => [
                        'id' => $o->id,
                        'order_no' => $o->order_no,
                        'total' => (int) $o->order_total,
                        'created_at' => $o->created_at?->format('Y-m-d'),
                    ])->values()->all();
            }

            return [
                'id' => $d->id,
                'trade_date' => $d->trade_date,
                'depositor' => $d->depositor,
                'acc_in' => (int) $d->acc_in,
                'remark' => $d->remark,
                'matched' => $d->isMatched(),
                'matched_order' => $d->matchedOrder ? [
                    'id' => $d->matchedOrder->id,
                    'order_no' => $d->matchedOrder->order_no,
                    'store_name' => $d->matchedOrder->store?->name,
                ] : null,
                'resolved_store' => $sid ? [
                    'id' => $sid,
                    'name' => $storeById[$sid]->name ?? null,
                ] : null,
                'candidates' => $cands,
            ];
        })->values()->all();

        return response()->json([
            'corp' => $corp,
            'accounts_error' => $accountsError,
            'accounts' => collect($accounts)->map(fn ($a) => [
                'bank_code' => $a->bankCode,
                'account_number' => $a->accountNumber,
                'account_name' => $a->accountName ?? null,
                'key' => $a->bankCode.'|'.$a->accountNumber,
            ])->values()->all(),
            'selected_acc' => $bankCode && $accountNumber ? "$bankCode|$accountNumber" : null,
            'jobs' => $jobs->map(fn (BankCollectJob $j) => [
                'id' => $j->id,
                'job_id' => $j->job_id,
                'job_state' => $j->job_state,
                'state_label' => $j->stateLabel(),
                'done' => $j->isDone(),
                'start_date' => $j->start_date,
                'end_date' => $j->end_date,
                'error_reason' => $j->error_reason,
            ])->all(),
            'selected_job_id' => $selected?->job_id,
            'deposits' => $depositRows,
            'stores' => $stores->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->all(),
            'summary' => [
                'total' => (int) $deposits->sum('acc_in'),
                'matched' => $deposits->filter->isMatched()->count(),
                'unmatched' => $deposits->reject->isMatched()->count(),
                'count' => $deposits->count(),
            ],
            'def_start' => now()->startOfMonth()->format('Y-m-d'),
            'def_end' => now()->format('Y-m-d'),
        ]);
    }

    public function requestJob(Request $request): JsonResponse
    {
        $this->guardHq($request);
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
            return response()->json(['message' => '수집 요청 실패: '.$e->getMessage()], 422);
        }

        $job = BankCollectJob::create([
            'corp_num' => $corp,
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'start_date' => $sDate,
            'end_date' => $eDate,
            'job_id' => $jobId,
            'job_state' => 1,
            'requested_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => '수집을 요청했습니다.',
            'job' => ['id' => $job->id, 'job_id' => $job->job_id, 'job_state' => $job->job_state],
        ], 201);
    }

    public function jobState(Request $request, BankCollectJob $job): JsonResponse
    {
        $this->guardHq($request);
        try {
            $state = $this->bank->getJobState($job->corp_num, $job->job_id);
            $job->update([
                'job_state' => (int) ($state->jobState ?? $job->job_state),
                'error_code' => isset($state->errorCode) && $state->errorCode !== '' ? (int) $state->errorCode : null,
                'error_reason' => $state->errorReason ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }

        return response()->json([
            'ok' => true,
            'state' => $job->job_state,
            'label' => $job->stateLabel(),
            'done' => $job->isDone(),
        ]);
    }

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

    public function mapDepositor(Request $request): JsonResponse
    {
        $this->guardHq($request);
        $data = $request->validate([
            'depositor_name' => ['required', 'string', 'max:120'],
            'store_id' => ['required', 'exists:stores,id'],
        ]);

        BankDepositorMapping::updateOrCreate(
            ['corp_num' => $this->corpNum(), 'depositor_name' => $data['depositor_name']],
            ['store_id' => $data['store_id']]
        );

        return response()->json(['message' => "입금자 '{$data['depositor_name']}' 매핑을 저장했습니다."]);
    }

    /** POST /api/v1/seller/bank/map-bulk — 여러 입금자 → 한 매장 일괄 매핑 */
    public function mapDepositorBulk(Request $request): JsonResponse
    {
        $this->guardHq($request);
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

        return response()->json(['message' => count($names).'명의 입금자를 한 매장으로 매핑했습니다.']);
    }

    public function match(Request $request): JsonResponse
    {
        $this->guardHq($request);
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

        return response()->json(['message' => '입금을 주문과 대사했습니다.']);
    }

    public function unmatch(Request $request, BankDeposit $deposit): JsonResponse
    {
        $this->guardHq($request);
        DB::transaction(function () use ($deposit) {
            if ($deposit->matched_order_id) {
                Order::where('id', $deposit->matched_order_id)->update(['paid_at' => null]);
            }
            $deposit->update(['matched_order_id' => null, 'confirmed_at' => null]);
        });

        return response()->json(['message' => '대사를 해제했습니다.']);
    }

    public function autoMatch(Request $request): JsonResponse
    {
        $this->guardHq($request);
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
                ->get(['id', 'store_amount', 'shipping_fee'])
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

        return response()->json(['message' => "자동 대사 {$matched}건을 처리했습니다.", 'matched' => $matched]);
    }

    public function flatRateUrl(Request $request): JsonResponse
    {
        $this->guardHq($request);
        try {
            return response()->json(['url' => $this->bank->getFlatRatePopUpURL($this->corpNum())]);
        } catch (\Throwable $e) {
            return response()->json(['message' => '정액제 신청 URL 발급 실패: '.$e->getMessage()], 422);
        }
    }
}
