@extends('portal.layout')
@section('title', '세금계산서 상세')

@section('content')
<div class="flex items-center justify-between mb-5 print:hidden">
    <a href="{{ route('portal.supplier.invoices.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600">← 세금계산서 목록</a>
    <div class="flex items-center gap-2">
        @if ($invoice->status === 'issued')
            <form method="POST" action="{{ route('portal.supplier.invoices.cancel', $invoice) }}"
                  onsubmit="return confirm('이 세금계산서를 발행취소합니다. 진행하시겠습니까?\n(국세청 전송 완료 후에는 취소되지 않을 수 있습니다.)')">
                @csrf
                <button type="submit" class="rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold px-5 py-2.5 transition">발행취소</button>
            </form>
        @elseif ($invoice->status === 'canceled')
            <span class="rounded-xl bg-rose-100 text-rose-700 font-bold px-4 py-2.5">취소됨</span>
        @endif
        <button onclick="window.print()" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-5 py-2.5 transition">🖨️ 인쇄</button>
    </div>
</div>

<div class="max-w-3xl">
    @include('portal.partials.invoice-document', ['invoice' => $invoice])
    @include('portal.partials.invoice-popbill-note', ['invoice' => $invoice])
</div>
@endsection
