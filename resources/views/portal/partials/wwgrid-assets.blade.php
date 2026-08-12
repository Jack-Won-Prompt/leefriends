{{-- wwGrid 에셋 로더 — 그리드를 쓰는 화면에서 @include('portal.partials.wwgrid-assets') 한 번 호출.
     한 페이지에 여러 그리드가 있어도 @once 로 파일은 한 번만 실린다. (README 1장·9장) --}}
@once
    @push('head')
        <link rel="stylesheet" href="{{ asset('vendor/wwgrid/wwGrid.css') }}?v={{ filemtime(public_path('vendor/wwgrid/wwGrid.css')) }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('vendor/wwgrid/wwGrid.js') }}?v={{ filemtime(public_path('vendor/wwgrid/wwGrid.js')) }}"></script>
    @endpush
@endonce
