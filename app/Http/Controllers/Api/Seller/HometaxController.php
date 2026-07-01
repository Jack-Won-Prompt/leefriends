<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\HometaxCollectJob;
use App\Services\Popbill\PopbillHometaxTaxinvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 모바일 앱 — 본사 매출/매입(홈택스 전자세금계산서 수집·조회).
 * 웹 Portal\Hq\HometaxTaxinvoiceController 와 동일 로직. 본사(hq) 전용.
 */
class HometaxController extends Controller
{
    use ResolvesSeller;

    public function __construct(private PopbillHometaxTaxinvoiceService $hometax)
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

    /** GET — 연동상태 + 수집이력 + 선택작업 목록/요약 */
    public function index(Request $request): JsonResponse
    {
        $this->guardHq($request);
        $corp = $this->corpNum();
        $type = $request->query('type') === HometaxCollectJob::TYPE_BUY
            ? HometaxCollectJob::TYPE_BUY : HometaxCollectJob::TYPE_SELL;
        $page = max(1, (int) $request->query('page', 1));

        $certExpire = $this->hometax->getCertificateExpireDate($corp);
        $flatRate = $this->hometax->getFlatRateState($corp);

        $jobs = HometaxCollectJob::where('corp_num', $corp)->orderByDesc('id')->limit(20)->get();

        $selected = null;
        if ($jid = $request->query('job_id')) {
            $selected = $jobs->firstWhere('job_id', $jid);
        }
        $selected ??= $jobs->where('ti_type', $type)->firstWhere('job_state', 3);

        $invoices = [];
        $summary = null;
        $pageCount = 1;
        $error = null;
        if ($selected && $selected->isDone()) {
            try {
                $list = $this->hometax->search($corp, $selected->job_id, [], [], [], $page, 20, 'D');
                $sum = $this->hometax->summary($corp, $selected->job_id);
                $invoices = collect($list->list ?? [])->map(fn ($r) => $this->invoiceRow($r))->values()->all();
                $pageCount = (int) ($list->pageCount ?? 1);
                $summary = [
                    'count' => (int) ($sum->count ?? 0),
                    'supply' => (int) ($sum->supplyCostTotal ?? 0),
                    'tax' => (int) ($sum->taxTotal ?? 0),
                    'amount' => (int) ($sum->amountTotal ?? 0),
                ];
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return response()->json([
            'corp' => $corp,
            'type' => $type,
            'cert_expire' => $certExpire,
            'flat_rate' => $flatRate ? [
                'referenceID' => $flatRate->referenceID ?? null,
                'contractDT' => $flatRate->contractDT ?? null,
                'useEndDate' => $flatRate->useEndDate ?? null,
                'baseDate' => $flatRate->baseDate ?? null,
                'state' => $flatRate->state ?? null,
                'closed' => $flatRate->closed ?? null,
            ] : null,
            'jobs' => $jobs->map(fn (HometaxCollectJob $j) => $this->jobRow($j))->all(),
            'selected_job_id' => $selected?->job_id,
            'summary' => $summary,
            'invoices' => $invoices,
            'page' => $page,
            'page_count' => $pageCount,
            'error' => $error,
        ]);
    }

    /** POST — 수집 요청 */
    public function requestJob(Request $request): JsonResponse
    {
        $this->guardHq($request);
        $data = $request->validate([
            'ti_type' => ['required', Rule::in([HometaxCollectJob::TYPE_SELL, HometaxCollectJob::TYPE_BUY])],
            'date_type' => ['nullable', Rule::in(['W', 'I', 'S'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'start_date.required' => '시작일을 선택해 주세요.',
            'end_date.after_or_equal' => '종료일은 시작일 이후여야 합니다.',
        ]);

        $corp = $this->corpNum();
        $sDate = str_replace('-', '', $data['start_date']);
        $eDate = str_replace('-', '', $data['end_date']);
        $dType = $data['date_type'] ?? 'W';

        try {
            $jobId = $this->hometax->requestJob($corp, $data['ti_type'], $sDate, $eDate, $dType);
        } catch (\Throwable $e) {
            return response()->json(['message' => '수집 요청 실패: '.$e->getMessage()], 422);
        }

        $job = HometaxCollectJob::create([
            'corp_num' => $corp,
            'ti_type' => $data['ti_type'],
            'date_type' => $dType,
            'start_date' => $sDate,
            'end_date' => $eDate,
            'job_id' => $jobId,
            'job_state' => 1,
            'requested_by' => $request->user()->id,
        ]);

        return response()->json(['message' => '수집을 요청했습니다.', 'job' => $this->jobRow($job)], 201);
    }

    /** GET — 수집 상태 폴링 */
    public function jobState(Request $request, HometaxCollectJob $job): JsonResponse
    {
        $this->guardHq($request);
        try {
            $state = $this->hometax->getJobState($job->corp_num, $job->job_id);
            $job->update([
                'job_state' => (int) ($state->jobState ?? $job->job_state),
                'collect_count' => isset($state->collectCount) ? (int) $state->collectCount : $job->collect_count,
                'error_code' => isset($state->errorCode) ? (int) $state->errorCode : null,
                'error_reason' => $state->errorReason ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }

        return response()->json([
            'ok' => true,
            'state' => $job->job_state,
            'label' => $job->stateLabel(),
            'count' => $job->collect_count,
            'done' => $job->isDone(),
            'error_code' => $job->error_code,
            'error_reason' => $job->error_reason,
        ]);
    }

    /** GET — 세금계산서 1건 상세 (국세청승인번호) */
    public function detail(Request $request): JsonResponse
    {
        $this->guardHq($request);
        $nts = (string) $request->query('nts');
        abort_if($nts === '', 404);

        try {
            $t = $this->hometax->getTaxinvoice($this->corpNum(), $nts);
        } catch (\Throwable $e) {
            return response()->json(['message' => '상세 조회 실패: '.$e->getMessage()], 422);
        }

        return response()->json(['data' => [
            'ntsconfirmNum' => $t->ntsconfirmNum ?? null,
            'writeDate' => $t->writeDate ?? null,
            'taxType' => $t->taxType ?? null,
            'purposeType' => $t->purposeType ?? null,
            'invoicerCorpNum' => $t->invoicerCorpNum ?? null,
            'invoicerCorpName' => $t->invoicerCorpName ?? null,
            'invoicerCEOName' => $t->invoicerCEOName ?? null,
            'invoicerAddr' => $t->invoicerAddr ?? null,
            'invoiceeCorpNum' => $t->invoiceeCorpNum ?? null,
            'invoiceeCorpName' => $t->invoiceeCorpName ?? null,
            'invoiceeCEOName' => $t->invoiceeCEOName ?? null,
            'invoiceeAddr' => $t->invoiceeAddr ?? null,
            'supplyCostTotal' => (int) ($t->supplyCostTotal ?? 0),
            'taxTotal' => (int) ($t->taxTotal ?? 0),
            'totalAmount' => (int) ($t->totalAmount ?? 0),
            'items' => collect($t->detailList ?? [])->map(fn ($d) => [
                'purchaseDT' => $d->purchaseDT ?? null,
                'itemName' => $d->itemName ?? null,
                'spec' => $d->spec ?? null,
                'qty' => $d->qty ?? null,
                'unitCost' => $d->unitCost ?? null,
                'supplyCost' => (int) ($d->supplyCost ?? 0),
                'tax' => (int) ($d->tax ?? 0),
                'remark' => $d->remark ?? null,
            ])->values()->all(),
        ]]);
    }

    /** GET — 공동인증서 등록 팝업 URL (앱이 브라우저로 오픈) */
    public function certUrl(Request $request): JsonResponse
    {
        $this->guardHq($request);
        try {
            return response()->json(['url' => $this->hometax->getCertificatePopUpURL($this->corpNum())]);
        } catch (\Throwable $e) {
            return response()->json(['message' => '인증서 등록 URL 발급 실패: '.$e->getMessage()], 422);
        }
    }

    /** GET — 정액제 신청 팝업 URL */
    public function flatRateUrl(Request $request): JsonResponse
    {
        $this->guardHq($request);
        try {
            return response()->json(['url' => $this->hometax->getFlatRatePopUpURL($this->corpNum())]);
        } catch (\Throwable $e) {
            return response()->json(['message' => '정액제 신청 URL 발급 실패: '.$e->getMessage()], 422);
        }
    }

    private function jobRow(HometaxCollectJob $j): array
    {
        return [
            'id' => $j->id,
            'job_id' => $j->job_id,
            'ti_type' => $j->ti_type,
            'type_label' => $j->typeLabel(),
            'job_state' => $j->job_state,
            'state_label' => $j->stateLabel(),
            'collect_count' => $j->collect_count,
            'start_date' => $j->start_date,
            'end_date' => $j->end_date,
            'done' => $j->isDone(),
            'error_reason' => $j->error_reason,
            'created_at' => $j->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function invoiceRow($r): array
    {
        return [
            'ntsconfirmNum' => $r->ntsconfirmNum ?? null,
            'writeDate' => $r->writeDate ?? null,
            'taxType' => $r->taxType ?? null,
            'invoicerCorpName' => $r->invoicerCorpName ?? null,
            'invoicerCorpNum' => $r->invoicerCorpNum ?? null,
            'invoiceeCorpName' => $r->invoiceeCorpName ?? null,
            'invoiceeCorpNum' => $r->invoiceeCorpNum ?? null,
            'supplyCostTotal' => (int) ($r->supplyCostTotal ?? 0),
            'taxTotal' => (int) ($r->taxTotal ?? 0),
            'totalAmount' => (int) ($r->totalAmount ?? 0),
        ];
    }
}
