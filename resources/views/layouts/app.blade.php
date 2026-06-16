<!DOCTYPE html>
<html lang="ko" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'LEEFRIENDS · 프리미엄 망고빙수 전문점')</title>
    <meta name="description" content="@yield('desc', '농익은 애플망고로 만드는 프리미엄 망고빙수 전문점 LEEFRIENDS. 사계절 디저트 카페 창업의 새로운 기준.')">

    <meta property="og:title" content="@yield('title', 'LEEFRIENDS · 프리미엄 망고빙수 전문점')">
    <meta property="og:description" content="@yield('desc', '농익은 애플망고로 만드는 프리미엄 망고빙수 전문점 LEEFRIENDS.')">
    <meta property="og:image" content="{{ asset('images/og.svg') }}">
    <meta property="og:type" content="website">

    <link rel="icon" href="{{ asset('images/menu/mango-cheese-bingsu.svg') }}">

    {{-- Pretendard --}}
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">

    {{-- Tailwind Play CDN --}}
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Pretendard Variable', 'Pretendard', 'Apple SD Gothic Neo', 'sans-serif'],
                    },
                    colors: {
                        mango: {
                            50:  '#FFF9ED',
                            100: '#FFF1D2',
                            200: '#FFE0A3',
                            300: '#FFCB6B',
                            400: '#FFB23D',
                            500: '#FF9F1C', // primary
                            600: '#F2784B', // accent orange
                            700: '#D45A1F',
                            800: '#A8430F',
                            900: '#7A3210',
                        },
                    },
                    boxShadow: {
                        soft: '0 18px 50px -20px rgba(242,120,75,0.45)',
                        card: '0 12px 40px -18px rgba(0,0,0,0.18)',
                    },
                    keyframes: {
                        floaty: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-12px)' } },
                        fadeup: { '0%': { opacity: 0, transform: 'translateY(24px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
                    },
                    animation: {
                        floaty: 'floaty 5s ease-in-out infinite',
                        fadeup: 'fadeup 0.7s ease-out both',
                    },
                },
            },
        }
    </script>
    <style>
        body { -webkit-font-smoothing: antialiased; }
        .text-balance { text-wrap: balance; }
        [x-cloak] { display: none !important; }
        .reveal { opacity: 0; transform: translateY(28px); transition: all .7s cubic-bezier(.2,.7,.2,1); }
        .reveal.in { opacity: 1; transform: none; }
        .nav-underline { position: relative; }
        .nav-underline::after { content: ''; position: absolute; left: 0; bottom: -6px; height: 2px; width: 0; background: #FF9F1C; transition: width .25s; }
        .nav-underline:hover::after { width: 100%; }
    </style>
    @stack('head')
</head>
<body class="font-sans text-neutral-800 bg-white antialiased">

    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    {{-- scroll reveal --}}
    <script>
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: 0.12 });
        document.querySelectorAll('.reveal').forEach(el => io.observe(el));

        // header solidify on scroll
        const header = document.getElementById('site-header');
        const onScroll = () => {
            if (window.scrollY > 30) header.classList.add('is-solid');
            else header.classList.remove('is-solid');
        };
        window.addEventListener('scroll', onScroll); onScroll();
    </script>
    @stack('scripts')
</body>
</html>
