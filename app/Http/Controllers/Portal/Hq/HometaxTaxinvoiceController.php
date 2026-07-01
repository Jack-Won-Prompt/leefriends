<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\HometaxCollectJob;
use App\Services\Popbill\PopbillHometaxTaxinvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * 본사 매출/매입 관리 — 홈택스 전자세금계산서 수집·조회.
 */
class HometaxTaxinvoiceController extends Controller
{
    public function __construct(private PopbillHometaxTaxinvoiceService $hometax)
    {
    }

    private function corpNum(): string
    {
        return preg_replace('/\D/', '', (string) config('popbill.hq.corp_num'));
    }

    public function index(Request $request)
    {
        $corp = $this->corpNum();
        $type = $request->query('type') === HometaxCollectJob::TYPE_BUY
            ? HometaxCollectJob::TYPE_BUY
            : HometaxCollectJob::TYPE_SELL;
        $page = max(1, (int) $request->query('page', 1));

        // 연동 상태 (실패해도 화면은 뜨도록 관대하게)
        $certExpire = $this->hometax->getCertificateExpireDate($corp);
        $flatRate = $this->hometax->getFlatRateState($corp);

        // 수집 이력 (최근)
        $jobs = HometaxCollectJob::where('corp_num', $corp)
            ->orderByDesc('id')->limit(20)->get();

        // 선택 작업: 명시된 job_id 또는 해당 유형 최신 완료 작업
        $selected = null;
        if ($jid = $request->query('job_id')) {
            $selected = $jobs->firstWhere('job_id', $jid);
        }
        $selected ??= $jobs->where('ti_type', $type)->firstWhere('job_state', 3);

        $list = null;
        $summary = null;
        $error = null;
        if ($selected && $selected->isDone()) {
            try {
                $list = $this->hometax->search($corp, $selected->job_id, [], [], [], $page, 20, 'D');
                $summary = $this->hometax->summary($corp, $selected->job_id);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return view('portal.hq.hometax.index', [
            'corp' => $corp,
            'type' => $type,
            'certExpire' => $certExpire,
            'flatRate' => $flatRate,
            'jobs' => $jobs,
            'selected' => $selected,
            'list' => $list,
            'summary' => $summary,
            'page' => $page,
            'error' => $error,
        ]);
    }

    /** 수집 요청 */
    public function requestJob(Request $request)
    {
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
            return back()->with('error', '수집 요청 실패: '.$e->getMessage());
        }

        HometaxCollectJob::create([
            'corp_num' => $corp,
            'ti_type' => $data['ti_type'],
            'date_type' => $dType,
            'start_date' => $sDate,
            'end_date' => $eDate,
            'job_id' => $jobId,
            'job_state' => 1,
            'requested_by' => Auth::id(),
        ]);

        return redirect()
            ->route('portal.hq.hometax.index', ['type' => $data['ti_type'], 'job_id' => $jobId])
            ->with('success', '수집을 요청했습니다. 잠시 후 완료됩니다.');
    }

    /** 수집 상태 폴링 (AJAX) */
    public function jobState(HometaxCollectJob $job)
    {
        try {
            $state = $this->hometax->getJobState($job->corp_num, $job->job_id);
            $job->update([
                'job_state' => (int) ($state->jobState ?? $job->job_state),
                'collect_count' => isset($state->collectCount) ? (int) $state->collectCount : $job->collect_count,
                'error_code' => isset($state->errorCode) ? (int) $state->errorCode : null,
                'error_reason' => $state->errorReason ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 200);
        }

        return response()->json([
            'ok' => true,
            'state' => $job->job_state,
            'label' => $job->stateLabel(),
            'count' => $job->collect_count,
            'done' => $job->isDone(),
            'errorCode' => $job->error_code,
            'errorReason' => $job->error_reason,
        ]);
    }

    /** 세금계산서 1건 상세 (모달 partial) */
    public function detail(Request $request)
    {
        $nts = (string) $request->query('nts');
        abort_if($nts === '', 404);

        try {
            $invoice = $this->hometax->getTaxinvoice($this->corpNum(), $nts);
        } catch (\Throwable $e) {
            return response('<div class="p-6 text-sm text-rose-600">상세 조회 실패: '.e($e->getMessage()).'</div>', 200);
        }

        return view('portal.hq.hometax.detail', ['t' => $invoice]);
    }

    /** 홈택스 수집 공동인증서 등록 팝업 */
    public function certUrl()
    {
        try {
            return redirect()->away($this->hometax->getCertificatePopUpURL($this->corpNum()));
        } catch (\Throwable $e) {
            return back()->with('error', '인증서 등록 URL 발급 실패: '.$e->getMessage());
        }
    }

    /** 정액제 신청 팝업 */
    public function flatRateUrl()
    {
        try {
            return redirect()->away($this->hometax->getFlatRatePopUpURL($this->corpNum()));
        } catch (\Throwable $e) {
            return back()->with('error', '정액제 신청 URL 발급 실패: '.$e->getMessage());
        }
    }
}
