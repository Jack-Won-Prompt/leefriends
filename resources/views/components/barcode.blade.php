@props(['value', 'height' => 38, 'width' => 1.4, 'format' => 'svg'])
@php
    // format: svg(웹/브라우저 기본) | html(DomPDF)
    $code = trim((string) $value);
    $out = '';
    if ($code !== '') {
        try {
            if ($format === 'html') {
                $out = (new \Picqer\Barcode\BarcodeGeneratorHTML())
                    ->getBarcode($code, \Picqer\Barcode\BarcodeGeneratorHTML::TYPE_CODE_128, (float) $width, (int) $height);
            } else {
                $svg = (new \Picqer\Barcode\BarcodeGeneratorSVG())
                    ->getBarcode($code, \Picqer\Barcode\BarcodeGeneratorSVG::TYPE_CODE_128, (float) $width, (int) $height);
                $pos = strpos($svg, '<svg'); // XML 선언 제거 → 인라인 삽입 안전
                $out = $pos !== false ? substr($svg, $pos) : $svg;
            }
        } catch (\Throwable $e) {
            $out = '';
        }
    }
@endphp
@if ($out)
    <div style="display:inline-block; text-align:center;">
        {!! $out !!}
        <div style="font-family:monospace; font-size:10px; letter-spacing:2px; margin-top:3px; color:#333; line-height:1;">{{ $code }}</div>
    </div>
@endif
