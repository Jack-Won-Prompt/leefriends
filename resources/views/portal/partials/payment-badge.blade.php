{{-- 입금(결제) 상태 배지 — $order 필요. 샘플(무상)은 표시 안 함 --}}
@unless ($order->isSample())
    @if ($order->paid_at)
        <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">💰 입금완료</span>
    @elseif ($order->status !== 'canceled')
        <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-400">입금대기</span>
    @endif
@endunless
