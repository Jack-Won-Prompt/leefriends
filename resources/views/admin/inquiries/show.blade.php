@extends('admin.layout')
@section('title', '창업문의 상세')

@section('content')
<a href="{{ route('admin.inquiries.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 목록으로</a>

<div class="grid lg:grid-cols-3 gap-6">
    {{-- detail --}}
    <div class="lg:col-span-2 rounded-2xl bg-white shadow-sm border border-neutral-100 p-7">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-black text-neutral-900">{{ $inquiry->name }}</h2>
            @include('admin.partials.status-badge', ['status' => $inquiry->status, 'label' => $inquiry->status_label])
        </div>
        <dl class="divide-y divide-neutral-100">
            @foreach ([
                '연락처' => $inquiry->phone,
                '이메일' => $inquiry->email ?: '-',
                '희망 창업지역' => $inquiry->region ?: '-',
                '창업 예산' => $inquiry->budget ?: '-',
                '접수일시' => $inquiry->created_at->format('Y년 m월 d일 H:i'),
            ] as $k => $v)
                <div class="flex py-3.5">
                    <dt class="w-32 shrink-0 text-sm font-bold text-neutral-500">{{ $k }}</dt>
                    <dd class="text-neutral-800">{{ $v }}</dd>
                </div>
            @endforeach
            <div class="py-3.5">
                <dt class="text-sm font-bold text-neutral-500 mb-2">문의 내용</dt>
                <dd class="text-neutral-800 whitespace-pre-line leading-relaxed bg-neutral-50 rounded-xl p-4">{{ $inquiry->message ?: '내용 없음' }}</dd>
            </div>
        </dl>
    </div>

    {{-- actions --}}
    <div class="space-y-6">
        <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-6">
            <h3 class="font-extrabold text-neutral-900 mb-4">상태 변경</h3>
            <form method="POST" action="{{ route('admin.inquiries.update', $inquiry) }}" class="space-y-3">
                @csrf @method('PATCH')
                <select name="status" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($inquiry->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="w-full rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold py-3 transition">변경 저장</button>
            </form>
        </div>

        <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-6">
            <h3 class="font-extrabold text-neutral-900 mb-4">빠른 연락</h3>
            <a href="tel:{{ $inquiry->phone }}" class="block text-center rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold py-3 mb-2">📞 전화 걸기</a>
            @if ($inquiry->email)
                <a href="mailto:{{ $inquiry->email }}" class="block text-center rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold py-3">✉️ 이메일 보내기</a>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.inquiries.destroy', $inquiry) }}" onsubmit="return confirm('이 문의를 삭제하시겠습니까?')">
            @csrf @method('DELETE')
            <button class="w-full rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold py-3 transition">문의 삭제</button>
        </form>
    </div>
</div>
@endsection
