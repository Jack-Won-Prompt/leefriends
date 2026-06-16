@extends('portal.layout')
@section('title', '세금계산서 상세')

@section('content')
<div class="flex items-center justify-between mb-5 print:hidden">
    <a href="{{ route('portal.hq.invoices.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600">← 세금계산서 목록</a>
    <button onclick="window.print()" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-5 py-2.5 transition">🖨️ 인쇄</button>
</div>

<div class="max-w-3xl">
    @include('portal.partials.invoice-document', ['invoice' => $invoice])

    @include('portal.partials.invoice-popbill-note')
</div>
@endsection
