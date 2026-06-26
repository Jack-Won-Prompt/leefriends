<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>거래명세서 {{ $statement->statement_no }}</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Pretendard Variable','Pretendard','sans-serif'] },
            colors: { mango: { 50:'#FFF9ED',100:'#FFF1D2',400:'#FFB23D',500:'#FF9F1C',600:'#F2784B',700:'#D45A1F' } },
        }}}
    </script>
</head>
<body class="font-sans bg-neutral-100 p-5">
<div class="max-w-3xl mx-auto">
    @include('portal.partials.supplier-statement-document', ['statement' => $statement])
</div>
@if (request()->boolean('print'))
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
@endif
</body>
</html>
