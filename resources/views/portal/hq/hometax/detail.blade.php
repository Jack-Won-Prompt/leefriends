@php
    $fmtDate = fn ($d) => $d ? \Illuminate\Support\Str::of((string) $d)->substr(0, 8)->replaceMatches('/(\d{4})(\d{2})(\d{2})/', '$1-$2-$3') : '-';
@endphp
<div class="p-6 space-y-5 text-sm">
    <div class="flex flex-wrap items-center gap-2">
        <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-neutral-100 text-neutral-600">{{ $t->taxType ?? '과세' }}</span>
        <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-neutral-100 text-neutral-600">{{ $t->purposeType ?? '' }}</span>
        <span class="text-neutral-400 text-xs">국세청승인번호 {{ $t->ntsconfirmNum ?? '-' }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl border border-neutral-200 p-4">
            <p class="text-xs font-bold text-neutral-400 mb-2">공급자</p>
            <p class="font-bold text-neutral-800">{{ $t->invoicerCorpName ?? '-' }}</p>
            <p class="text-neutral-500 text-xs mt-0.5">{{ $t->invoicerCorpNum ?? '' }} · {{ $t->invoicerCEOName ?? '' }}</p>
            <p class="text-neutral-500 text-xs mt-1">{{ $t->invoicerAddr ?? '' }}</p>
        </div>
        <div class="rounded-xl border border-neutral-200 p-4">
            <p class="text-xs font-bold text-neutral-400 mb-2">공급받는자</p>
            <p class="font-bold text-neutral-800">{{ $t->invoiceeCorpName ?? '-' }}</p>
            <p class="text-neutral-500 text-xs mt-0.5">{{ $t->invoiceeCorpNum ?? '' }} · {{ $t->invoiceeCEOName ?? '' }}</p>
            <p class="text-neutral-500 text-xs mt-1">{{ $t->invoiceeAddr ?? '' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 text-center">
        <div class="rounded-xl bg-neutral-50 p-3">
            <p class="text-xs text-neutral-400">작성일자</p>
            <p class="font-bold text-neutral-800 mt-1">{{ $fmtDate($t->writeDate ?? null) }}</p>
        </div>
        <div class="rounded-xl bg-neutral-50 p-3">
            <p class="text-xs text-neutral-400">공급가액</p>
            <p class="font-bold text-neutral-800 mt-1 tabular-nums">{{ number_format((int) ($t->supplyCostTotal ?? 0)) }}</p>
        </div>
        <div class="rounded-xl bg-neutral-50 p-3">
            <p class="text-xs text-neutral-400">세액</p>
            <p class="font-bold text-neutral-800 mt-1 tabular-nums">{{ number_format((int) ($t->taxTotal ?? 0)) }}</p>
        </div>
    </div>

    @if (! empty($t->detailList))
    <div>
        <p class="text-xs font-bold text-neutral-400 mb-2">품목</p>
        <table class="w-full text-xs border border-neutral-200 rounded-lg overflow-hidden">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left px-3 py-2">품목</th>
                    <th class="text-right px-3 py-2 w-16">수량</th>
                    <th class="text-right px-3 py-2 w-24">공급가액</th>
                    <th class="text-right px-3 py-2 w-20">세액</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($t->detailList as $d)
                    <tr>
                        <td class="px-3 py-2">{{ $d->itemName ?? '-' }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $d->qty ?? '' }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format((int) ($d->supplyCost ?? 0)) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ number_format((int) ($d->tax ?? 0)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="flex items-center justify-between pt-2 border-t border-neutral-100">
        <span class="text-neutral-400 text-xs">합계금액</span>
        <span class="text-lg font-black text-neutral-900 tabular-nums">{{ number_format((int) ($t->totalAmount ?? 0)) }}원</span>
    </div>
</div>
