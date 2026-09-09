@php
    $tone = ['good' => '#0a7d3f', 'ok' => '#b8860b', 'caution' => '#c0392b'][$mango['grade']['tone']] ?? '#0a7d3f';
    $toneBg = ['good' => '#e8f6ee', 'ok' => '#fbf3df', 'caution' => '#fbeaea'][$mango['grade']['tone']] ?? '#e8f6ee';
    $plan = $mango['plan'];
@endphp
<section style="margin-top:28px;border:1px solid #e6ebf3;border-radius:14px;padding:22px 24px;background:#fff;">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <h2 style="font-size:18px;font-weight:800;color:#100f14;margin:0;">🍧 리프랜즈 개점 장단점 분석</h2>
        <span style="display:inline-flex;align-items:center;gap:6px;background:{{ $toneBg }};color:{{ $tone }};font-weight:800;font-size:13px;padding:4px 12px;border-radius:999px;">
            {{ $mango['grade']['label'] }} · 적합도 {{ $mango['grade']['score'] }}점
        </span>
        @if (! empty($mango['ai']))
            <span style="display:inline-flex;align-items:center;gap:5px;background:#eef2ff;color:#4f46e5;font-weight:800;font-size:11.5px;padding:4px 10px;border-radius:999px;">
                🤖 AI 분석{{ ! empty($mango['ai_model']) ? ' · '.$mango['ai_model'] : '' }}
            </span>
        @endif
        @if (! empty($aiAvailable))
            <form method="POST" action="{{ route('market.analyses.mango_ai', $analysis) }}" style="margin-left:auto;">
                @csrf
                <button type="submit" style="background:#4f46e5;color:#fff;font-weight:700;font-size:12px;border:0;border-radius:8px;padding:7px 14px;cursor:pointer;">
                    🤖 {{ ! empty($mango['ai']) ? 'AI 분석 재생성' : 'AI 종합 분석 생성' }}
                </button>
            </form>
        @endif
    </div>
    <p style="margin:10px 0 0;color:#48505b;font-size:13.5px;line-height:1.65;">{{ $mango['summary'] }}</p>

    {{-- 장점 / 단점 --}}
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:18px;">
        <div style="flex:1;min-width:260px;border:1px solid #d7ecdf;border-radius:12px;padding:16px;background:#f6fbf8;">
            <p style="margin:0 0 10px;font-weight:800;color:#0a7d3f;font-size:14px;">👍 장점 ({{ count($mango['pros']) }})</p>
            @forelse ($mango['pros'] as $it)
                <div style="margin-bottom:10px;">
                    <p style="margin:0;font-weight:700;color:#173a28;font-size:13px;">· {{ $it['title'] }}</p>
                    <p style="margin:2px 0 0 12px;color:#4b5a51;font-size:12.5px;line-height:1.55;">{{ $it['detail'] }}</p>
                </div>
            @empty
                <p style="margin:0;color:#8a938c;font-size:12.5px;">뚜렷한 장점 신호가 확인되지 않았습니다.</p>
            @endforelse
        </div>
        <div style="flex:1;min-width:260px;border:1px solid #f0d7d7;border-radius:12px;padding:16px;background:#fdf7f7;">
            <p style="margin:0 0 10px;font-weight:800;color:#c0392b;font-size:14px;">👎 단점 · 리스크 ({{ count($mango['cons']) }})</p>
            @forelse ($mango['cons'] as $it)
                <div style="margin-bottom:10px;">
                    <p style="margin:0;font-weight:700;color:#3a1717;font-size:13px;">· {{ $it['title'] }}</p>
                    <p style="margin:2px 0 0 12px;color:#5a4b4b;font-size:12.5px;line-height:1.55;">{{ $it['detail'] }}</p>
                </div>
            @empty
                <p style="margin:0;color:#8a938c;font-size:12.5px;">뚜렷한 단점 신호가 확인되지 않았습니다.</p>
            @endforelse
        </div>
    </div>

    {{-- 운영 조건 코멘트 --}}
    <div style="margin-top:16px;border:1px solid #e6ebf3;border-radius:12px;padding:16px;background:#fafbfd;">
        <p style="margin:0 0 10px;font-weight:800;color:#100f14;font-size:14px;">🏪 매장 운영 조건 종합</p>
        @foreach ($mango['planNotes'] as $note)
            @php $nc = ['pro'=>'#0a7d3f','con'=>'#c0392b','info'=>'#3a6ea5'][$note['tone']] ?? '#3a6ea5';
                 $ni = ['pro'=>'✔','con'=>'✕','info'=>'ℹ'][$note['tone']] ?? 'ℹ'; @endphp
            <div style="margin-bottom:8px;">
                <p style="margin:0;font-weight:700;font-size:13px;color:{{ $nc }};">{{ $ni }} {{ $note['title'] }}</p>
                <p style="margin:2px 0 0 16px;color:#4b5560;font-size:12.5px;line-height:1.55;">{{ $note['detail'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- 운영 조건 입력 폼 --}}
    <form method="POST" action="{{ route('market.analyses.mango_plan', $analysis) }}"
          style="margin-top:16px;border-top:1px dashed #e0e6ef;padding-top:16px;display:flex;gap:20px;align-items:flex-end;flex-wrap:wrap;">
        @csrf
        <div>
            <label style="display:block;font-size:12px;font-weight:700;color:#6b7480;margin-bottom:5px;">홀 테이블 수</label>
            <input type="number" name="hall_tables" min="0" max="500" value="{{ $plan['hall_tables'] ?? '' }}" placeholder="예: 8"
                   style="width:110px;border:1px solid #cdd5e0;border-radius:8px;padding:8px 10px;font-size:13px;">
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:700;color:#6b7480;margin-bottom:5px;">매장 평수</label>
            <input type="number" name="area_pyeong" min="0" max="1000" value="{{ $plan['area_pyeong'] ?? '' }}" placeholder="예: 15"
                   style="width:110px;border:1px solid #cdd5e0;border-radius:8px;padding:8px 10px;font-size:13px;">
        </div>
        <label style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:#333;cursor:pointer;">
            <input type="checkbox" name="coupang" value="1" @checked($plan['coupang'] ?? false) style="width:16px;height:16px;"> 쿠팡잇츠 연계
        </label>
        <label style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:#333;cursor:pointer;">
            <input type="checkbox" name="baemin" value="1" @checked($plan['baemin'] ?? false) style="width:16px;height:16px;"> 배민 연계
        </label>
        <button type="submit" style="background:#f5a623;color:#fff;font-weight:800;font-size:13px;border:0;border-radius:8px;padding:9px 20px;cursor:pointer;">
            운영 조건 반영
        </button>
        <span style="font-size:11.5px;color:#9aa2ad;">입력하면 장단점·PDF에 매장 운영 방식까지 반영됩니다.</span>
    </form>
</section>
