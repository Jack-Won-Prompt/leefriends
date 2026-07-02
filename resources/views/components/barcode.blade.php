@props(['value', 'height' => 38, 'width' => 1.4])
@php
    $code = trim((string) $value);
    $bars = '';
    if ($code !== '') {
        try {
            $bars = (new \Picqer\Barcode\BarcodeGeneratorHTML())
                ->getBarcode($code, \Picqer\Barcode\BarcodeGeneratorHTML::TYPE_CODE_128, (float) $width, (int) $height);
        } catch (\Throwable $e) {
            $bars = '';
        }
    }
@endphp
@if ($bars)
    <div style="display:inline-block; text-align:center; line-height:0;">
        {!! $bars !!}
        <div style="font-family:monospace; font-size:10px; letter-spacing:2px; margin-top:3px; line-height:1; color:#333;">{{ $code }}</div>
    </div>
@endif
