<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\Statement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

/**
 * 매장 거래명세서 수취 — 본사가 발송한 거래명세서 조회·PDF.
 */
class StatementController extends Controller
{
    public function index()
    {
        $storeId = Auth::user()->store_id;
        abort_unless($storeId, 403, '연결된 매장이 없습니다.');

        $statements = Statement::where('store_id', $storeId)
            ->latest('sent_at')->paginate(20);

        return view('portal.store.statements.index', compact('statements'));
    }

    public function pdf(Statement $statement)
    {
        abort_unless((int) $statement->store_id === (int) Auth::user()->store_id, 403);

        $store = $statement->storeForRender();

        return Pdf::loadView('portal.hq.statements.pdf', [
            'store' => $store,
            'lines' => $statement->items,
            'total' => $statement->total,
            'date' => $statement->issueDate(),
        ])->setPaper('a4')->stream('거래명세서.pdf');
    }
}
