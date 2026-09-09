@php
    $tone = ['good' => '#0a7d3f', 'ok' => '#b8860b', 'caution' => '#c0392b'][$mango['grade']['tone']] ?? '#0a7d3f';
    $toneBg = ['good' => '#e8f6ee', 'ok' => '#fbf3df', 'caution' => '#fbeaea'][$mango['grade']['tone']] ?? '#e8f6ee';
    $plan = $mango['plan'];
@endphp
<div style="page-break-before: always;"></div>
<section style="margin-top:4mm;">
    <h2 style="font-size:13pt;font-weight:700;color:#100f14;margin:0 0 1mm;">리프랜즈 개점 장단점 분석</h2>
    <div style="background:{{ $toneBg }};border-radius:2mm;padding:2mm 3mm;margin-bottom:2mm;">
        <span style="color:{{ $tone }};font-weight:700;font-size:10.5pt;">{{ $mango['grade']['label'] }} · 적합도 {{ $mango['grade']['score'] }}점</span>
        @if (! empty($mango['ai']))
            <span style="color:#4f46e5;font-weight:700;font-size:8.5pt;"> · AI 분석{{ ! empty($mango['ai_model']) ? ' ('.$mango['ai_model'].')' : '' }}</span>
        @endif
    </div>
    <p style="color:#3a4149;font-size:9pt;line-height:1.6;margin:0 0 3mm;">{{ $mango['summary'] }}</p>

    <table style="width:100%;border-collapse:collapse;margin-bottom:3mm;">
        <tr>
            <td style="width:50%;vertical-align:top;padding:2.5mm;border:0.4pt solid #d7ecdf;background:#f6fbf8;">
                <p style="margin:0 0 1.5mm;font-weight:700;color:#0a7d3f;font-size:9.5pt;">장점 ({{ count($mango['pros']) }})</p>
                @forelse ($mango['pros'] as $it)
                    <p style="margin:0 0 1.5mm;font-size:8.3pt;line-height:1.5;color:#2f3a33;">
                        <b>· {{ $it['title'] }}</b><br>
                        <span style="color:#4b5a51;">{{ $it['detail'] }}</span>
                    </p>
                @empty
                    <p style="margin:0;font-size:8.3pt;color:#8a938c;">뚜렷한 장점 신호 없음.</p>
                @endforelse
            </td>
            <td style="width:50%;vertical-align:top;padding:2.5mm;border:0.4pt solid #f0d7d7;background:#fdf7f7;">
                <p style="margin:0 0 1.5mm;font-weight:700;color:#c0392b;font-size:9.5pt;">단점 · 리스크 ({{ count($mango['cons']) }})</p>
                @forelse ($mango['cons'] as $it)
                    <p style="margin:0 0 1.5mm;font-size:8.3pt;line-height:1.5;color:#3a2f2f;">
                        <b>· {{ $it['title'] }}</b><br>
                        <span style="color:#5a4b4b;">{{ $it['detail'] }}</span>
                    </p>
                @empty
                    <p style="margin:0;font-size:8.3pt;color:#8a938c;">뚜렷한 단점 신호 없음.</p>
                @endforelse
            </td>
        </tr>
    </table>

    <div style="border:0.4pt solid #e0e6ef;background:#fafbfd;border-radius:2mm;padding:2.5mm 3mm;">
        <p style="margin:0 0 1.5mm;font-weight:700;color:#100f14;font-size:9.5pt;">매장 운영 조건 종합</p>
        @php
            $t = $plan['hall_tables'] === null ? '미입력' : $plan['hall_tables'].'테이블';
            $area = ($plan['area_pyeong'] ?? null) === null ? '' : ' · '.$plan['area_pyeong'].'평';
            $d = $plan['coupang'] && $plan['baemin'] ? '쿠팡잇츠·배민 연계' : ($plan['coupang'] ? '쿠팡잇츠 연계' : ($plan['baemin'] ? '배민 연계' : '배달 미연계'));
        @endphp
        <p style="margin:0 0 2mm;font-size:8.5pt;color:#48505b;">입력 조건 — 홀 {{ $t }}{{ $area }} · {{ $d }}</p>
        @foreach ($mango['planNotes'] as $note)
            @php $nc = ['pro'=>'#0a7d3f','con'=>'#c0392b','info'=>'#3a6ea5'][$note['tone']] ?? '#3a6ea5'; @endphp
            <p style="margin:0 0 1.2mm;font-size:8.3pt;line-height:1.5;">
                <b style="color:{{ $nc }};">{{ $note['title'] }}</b>
                <span style="color:#4b5560;"> — {{ $note['detail'] }}</span>
            </p>
        @endforeach
    </div>
</section>
