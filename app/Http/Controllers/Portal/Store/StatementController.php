<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\Statement;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

/**
 * 매장 거래명세서 수취 — 본사가 발송한 거래명세서 조회·PDF.
 */
class StatementController extends Controller
{
    use \App\Support\FiltersByDate;

    public function index(\Illuminate\Http\Request $request)
    {
        $storeId = Auth::user()->store_id;
        abort_unless($storeId, 403, '연결된 매장이 없습니다.');

        [$from, $to] = $this->dateRange($request);
        $query = Statement::where('store_id', $storeId)->latest('sent_at');
        $this->applyDateRange($query, $from, $to, 'sent_at');
        $statements = $query->paginate(20)->withQueryString();

        return view('portal.store.statements.index', compact('statements', 'from', 'to'));
    }

    public function pdf(Statement $statement)
    {
        $this->authorizeOwn($statement);

        // 최초 열람 자동 기록
        if (! $statement->viewed_at) {
            $statement->update(['viewed_at' => now()]);
        }

        $store = $statement->storeForRender();
        $seq = Statement::where('store_id', $statement->store_id)->whereDate('sent_at', $statement->sent_at)->where('id', '<=', $statement->id)->count();

        return Pdf::loadView('portal.hq.statements.pdf', [
            'store' => $store,
            'lines' => $statement->items,
            'total' => $statement->total,
            'date' => $statement->issueDate(),
        ])->setPaper('a4')->stream(\App\Support\StatementFile::name($statement->store_name, $statement->issueDate(), max(1, $seq)));
    }

    /** 매장 확인 처리 → 본사 알림 */
    public function confirm(Statement $statement, NotificationService $notify)
    {
        $this->authorizeOwn($statement);

        if (! $statement->confirmed_at) {
            $statement->update([
                'confirmed_at' => now(),
                'confirmed_by' => Auth::id(),
                'viewed_at' => $statement->viewed_at ?? now(),
            ]);

            // 본사에 확인 알림 (웹 토스트 + 앱 FCM)
            $notify->notifyUsers(
                User::where('role', 'hq')->get(), 'statement_confirmed',
                '✅ 거래명세서 확인됨',
                "{$statement->store_name} 매장이 {$statement->issueDate()->format('Y.m.d')} 거래명세서를 확인했습니다.",
                ['statement_id' => $statement->id]
            );
        }

        return back()->with('success', '거래명세서를 확인 처리했습니다. 본사에 통보됩니다.');
    }

    private function authorizeOwn(Statement $statement): void
    {
        abort_unless((int) $statement->store_id === (int) Auth::user()->store_id, 403);
    }
}
