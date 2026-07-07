<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Mail\StatementMail;
use App\Models\Statement;
use App\Models\Store;
use App\Models\SupplyProduct;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * 본사 — 거래명세서 작성(매장·품목 선택, 자동 계산) → 미리보기 / 이메일(PDF) 전송.
 */
class StatementController extends Controller
{
    /** 발송 이력 목록 */
    public function index()
    {
        return view('portal.hq.statements.index', [
            'statements' => Statement::with(['store', 'sender', 'taxInvoice', 'order'])->latest('sent_at')->paginate(20),
        ]);
    }

    /** 거래명세서 인쇄 전용 페이지 (?print=1 자동 인쇄) */
    public function print(Statement $statement)
    {
        $statement->loadMissing(['store', 'taxInvoice']);

        return view('portal.print.hq-statement', compact('statement'));
    }

    public function create()
    {
        return view('portal.hq.statements.create', [
            'stores' => Store::orderBy('name')->get(['id', 'name', 'email', 'region', 'postcode', 'address', 'address_detail']),
            'catalog' => $this->catalog(),
        ]);
    }

    /** PDF 미리보기 (브라우저 인라인) */
    public function preview(Request $request)
    {
        [$store, $lines, $total, $date] = $this->build($request);
        $seq = Statement::where('store_id', $store->id)->whereDate('created_at', now())->count() + 1;

        return $this->buildPdf($store, $lines, $total, $date)->stream(\App\Support\StatementFile::name($store->name, $date, $seq));
    }

    /** 매장 이메일로 PDF 전송 */
    public function send(Request $request)
    {
        [$store, $lines, $total, $date] = $this->build($request);

        if (! $store->email) {
            return back()->withErrors(['email' => "«{$store->name}» 매장에 이메일이 없습니다. 매장 관리에서 이메일을 먼저 등록하세요."])->withInput();
        }

        $this->mailStatement($store, $lines, $total, null, $date);

        // 발송 이력 저장 (스냅샷) — 매장 수취 화면에 즉시 노출됨
        $statement = Statement::create([
            'store_id' => $store->id,
            'store_name' => $store->name,
            'email' => $store->email,
            'statement_date' => $date->toDateString(),
            'item_count' => count($lines),
            'total' => $total,
            'items' => $lines,
            'sent_by' => Auth::id(),
            'sent_at' => now(),
        ]);

        // 매장에 알림 (웹 토스트 + 앱 FCM)
        app(\App\Services\Notification\NotificationService::class)->notifyStore(
            $store->id, 'statement', '🧾 거래명세서 도착',
            "{$date->format('Y.m.d')} 거래명세서가 도착했습니다. (".number_format($total).'원)',
            ['statement_id' => $statement->id]
        );

        return redirect()->route('portal.hq.statements.index')
            ->with('success', "«{$store->name}»({$store->email})로 거래명세서를 전송했습니다.");
    }

    /** 이력의 PDF 재생성 (인라인 미리보기) */
    public function pdf(Statement $statement)
    {
        $store = $statement->storeForRender();
        $seq = Statement::where('store_id', $statement->store_id)->whereDate('sent_at', $statement->sent_at)->where('id', '<=', $statement->id)->count();

        return $this->buildPdf($store, $statement->items, $statement->total, $statement->issueDate())
            ->stream(\App\Support\StatementFile::name($statement->store_name, $statement->issueDate(), max(1, $seq)));
    }

    /** 이력 재전송 */
    public function resend(Statement $statement)
    {
        $store = $statement->storeForRender();
        $email = $store->email ?: $statement->email;
        if (! $email) {
            return back()->withErrors(['email' => '수신 이메일이 없어 재전송할 수 없습니다.']);
        }

        $this->mailStatement($store, $statement->items, $statement->total, $email, $statement->issueDate());
        $statement->increment('resend_count');
        $statement->update(['sent_at' => now()]);

        return back()->with('success', "«{$statement->store_name}»({$email})로 거래명세서를 재전송했습니다.");
    }

    /** PDF 생성 + 메일 발송 */
    private function mailStatement(Store $store, array $lines, int $total, ?string $email = null, $date = null): void
    {
        $pdf = $this->buildPdf($store, $lines, $total, $date);
        $fileName = '거래명세서_'.$store->name.'_'.($date ?: now())->format('Ymd').'.pdf';
        Mail::to($email ?: $store->email)->send(new StatementMail($store, $lines, $total, $pdf->output(), $fileName));
    }

    /** 요청(매장+품목) → 매장/라인/합계 산출 (단가는 서버 DB 기준 재계산) */
    private function build(Request $request): array
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'statement_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:supply_products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99999'],
        ], [
            'items.required' => '품목을 1개 이상 선택해 주세요.',
        ]);

        $date = ! empty($data['statement_date']) ? \Illuminate\Support\Carbon::parse($data['statement_date'])->startOfDay() : now();
        $store = Store::findOrFail($data['store_id']);
        $products = SupplyProduct::with('units')
            ->whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

        $lines = [];
        $total = 0;
        foreach ($data['items'] as $it) {
            $p = $products[$it['product_id']] ?? null;
            if (! $p) {
                continue;
            }
            $u = $p->units->firstWhere('is_default', true) ?? $p->units->first();
            $price = (int) ($u->store_price ?? $p->store_price);
            $qty = (int) $it['qty'];
            $amount = $price * $qty;
            $lines[] = [
                'code' => $p->code,
                'name' => $p->name,
                'unit' => $u->name ?? $p->unit,
                'qty' => $qty,
                'price' => $price,
                'amount' => $amount,
                'tax_type' => $p->tax_type ?? 'exc',
            ];
        }

        // 부가세 포함 합계 (과세 공급가액 + 부가세 + 면세)
        $total = \App\Support\TaxSummary::fromLines($lines)['total'];

        return [$store, $lines, $total, $date];
    }

    private function buildPdf(Store $store, array $lines, int $total, $date = null)
    {
        return Pdf::loadView('portal.hq.statements.pdf', [
            'store' => $store,
            'lines' => $lines,
            'total' => $total,
            'date' => $date ?: now(),
        ])->setPaper('a4');
    }

    /** 발주 가능한(활성+승인) 품목 카탈로그 — 기본단위 판매가 기준 */
    private function catalog()
    {
        return SupplyProduct::active()->approved()->with('units')->catalogOrder()->get()->map(function ($p) {
            $u = $p->units->firstWhere('is_default', true) ?? $p->units->first();

            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'category' => $p->category,
                'unit' => $u->name ?? $p->unit,
                'price' => (int) ($u->store_price ?? $p->store_price),
            ];
        })->values();
    }
}
