@php $inv = $invoice ?? null; @endphp
<div class="mt-4 rounded-xl bg-sky-50 border border-sky-200 px-5 py-4 text-sm text-sky-800 print:hidden">
    <p class="font-bold">ℹ️ 전자세금계산서 안내</p>
    <p class="mt-1 leading-relaxed text-sky-700">
        현재는 <b>내부 발행(집계·출력)</b> 단계입니다. 추후 <b>팝빌(Popbill) 전자세금계산서</b> API 연동 시
        이 계산서가 국세청에 정식 발행되며, 발행 후 <b>국세청 승인번호</b>가 여기에 표시됩니다.
        @if ($inv && $inv->nts_confirm_num)
            <br>국세청 승인번호: <span class="font-bold">{{ $inv->nts_confirm_num }}</span>
        @endif
    </p>
</div>
