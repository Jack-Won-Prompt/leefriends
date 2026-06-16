<?php
/**
 * Generates modern mango-themed SVG placeholder images for the LEEFRIENDS site.
 * Run: php scripts/gen_images.php
 */

$root = dirname(__DIR__) . '/public/images';
@mkdir("$root/menu", 0777, true);
@mkdir("$root/hero", 0777, true);
@mkdir("$root/brand", 0777, true);
@mkdir("$root/store", 0777, true);

/** Mango palette */
$palettes = [
    ['#FFE29A', '#FFB347', '#FF8A3D'], // classic mango
    ['#FFF3C4', '#FFD15C', '#FF9F1C'], // sunny
    ['#FFE0B2', '#FF8A65', '#F4511E'], // sunset
    ['#FFF6D6', '#FFCA3A', '#FB8500'], // gold
    ['#FFEFD6', '#FFB562', '#F2784B'], // peachy
];

function card_svg(string $title, string $sub, array $p, string $glyph = '🥭'): string
{
    [$c1, $c2, $c3] = $p;
    $t = htmlspecialchars($title, ENT_QUOTES);
    $s = htmlspecialchars($sub, ENT_QUOTES);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="$c1"/>
      <stop offset="0.55" stop-color="$c2"/>
      <stop offset="1" stop-color="$c3"/>
    </linearGradient>
    <radialGradient id="h" cx="0.3" cy="0.25" r="0.8">
      <stop offset="0" stop-color="#ffffff" stop-opacity="0.55"/>
      <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
    </radialGradient>
    <filter id="soft" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="8" stdDeviation="18" flood-color="#000" flood-opacity="0.12"/>
    </filter>
  </defs>
  <rect width="800" height="600" fill="url(#g)"/>
  <rect width="800" height="600" fill="url(#h)"/>
  <circle cx="640" cy="120" r="150" fill="#ffffff" opacity="0.10"/>
  <circle cx="120" cy="500" r="110" fill="#ffffff" opacity="0.08"/>
  <g filter="url(#soft)">
    <circle cx="400" cy="250" r="120" fill="#ffffff" opacity="0.92"/>
    <text x="400" y="250" font-size="120" text-anchor="middle" dominant-baseline="central">$glyph</text>
  </g>
  <text x="400" y="430" font-family="'Pretendard','Apple SD Gothic Neo',sans-serif" font-size="54" font-weight="800" fill="#ffffff" text-anchor="middle">$t</text>
  <text x="400" y="480" font-family="'Pretendard',sans-serif" font-size="26" font-weight="500" fill="#ffffff" opacity="0.85" text-anchor="middle" letter-spacing="3">$s</text>
</svg>
SVG;
}

function hero_svg(string $kicker, string $title, string $sub, array $p): string
{
    [$c1, $c2, $c3] = $p;
    $k = htmlspecialchars($kicker, ENT_QUOTES);
    $t = htmlspecialchars($title, ENT_QUOTES);
    $s = htmlspecialchars($sub, ENT_QUOTES);
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1920" height="900" viewBox="0 0 1920 900">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="$c1"/>
      <stop offset="0.5" stop-color="$c2"/>
      <stop offset="1" stop-color="$c3"/>
    </linearGradient>
  </defs>
  <rect width="1920" height="900" fill="url(#g)"/>
  <circle cx="1500" cy="220" r="380" fill="#ffffff" opacity="0.12"/>
  <circle cx="300" cy="760" r="260" fill="#ffffff" opacity="0.10"/>
  <circle cx="1660" cy="700" r="120" fill="#ffffff" opacity="0.14"/>
  <text x="160" y="380" font-family="'Pretendard',sans-serif" font-size="30" font-weight="700" fill="#ffffff" opacity="0.9" letter-spacing="8">$k</text>
  <text x="160" y="490" font-family="'Pretendard',sans-serif" font-size="96" font-weight="900" fill="#ffffff">$t</text>
  <text x="160" y="560" font-family="'Pretendard',sans-serif" font-size="34" font-weight="500" fill="#ffffff" opacity="0.9">$s</text>
  <text x="1500" y="240" font-size="320" text-anchor="middle" dominant-baseline="central">🥭</text>
</svg>
SVG;
}

// ---- Menus ----
$menus = [
    ['mango-cheese-bingsu', '망고치즈빙수', 'MANGO CHEESE', 0],
    ['apple-mango-bingsu', '애플망고빙수', 'APPLE MANGO', 1],
    ['mango-yogurt-bingsu', '망고요거트빙수', 'MANGO YOGURT', 2],
    ['tropical-mango-bingsu', '트로피컬망고빙수', 'TROPICAL MANGO', 3],
    ['mango-patbingsu', '망고팥빙수', 'MANGO PATBINGSU', 4],
    ['mango-choco-bingsu', '망고초코빙수', 'MANGO CHOCO', 1],
    ['mango-ade', '망고에이드', 'MANGO ADE', 2],
    ['mango-smoothie', '망고스무디', 'MANGO SMOOTHIE', 0],
    ['mango-yogurt-smoothie', '망고요거트스무디', 'YOGURT SMOOTHIE', 3],
    ['mango-juice', '망고주스', 'MANGO JUICE', 4],
    ['mango-cream-cake', '망고크림케이크', 'CREAM CAKE', 1],
    ['mango-tart', '망고타르트', 'MANGO TART', 0],
    ['mango-pudding', '망고푸딩', 'MANGO PUDDING', 2],
];
$glyphs = ['망고치즈빙수' => '🍧', '애플망고빙수' => '🍧', '망고요거트빙수' => '🍨', '트로피컬망고빙수' => '🍧', '망고팥빙수' => '🍧', '망고초코빙수' => '🍫', '망고에이드' => '🥤', '망고스무디' => '🥤', '망고요거트스무디' => '🥤', '망고주스' => '🧃', '망고크림케이크' => '🍰', '망고타르트' => '🥧', '망고푸딩' => '🍮'];
foreach ($menus as [$slug, $name, $en, $pi]) {
    $glyph = $glyphs[$name] ?? '🥭';
    file_put_contents("$root/menu/$slug.svg", card_svg($name, $en, $palettes[$pi], $glyph));
}

// ---- Hero slides ----
file_put_contents("$root/hero/slide1.svg", hero_svg('PREMIUM MANGO DESSERT', '농익은 애플망고, 그대로', '한 입에 퍼지는 진짜 망고의 계절', $palettes[1]));
file_put_contents("$root/hero/slide2.svg", hero_svg('SIGNATURE BINGSU', '망고치즈빙수', '부드러운 우유빙수 위 가득한 생망고', $palettes[0]));
file_put_contents("$root/hero/slide3.svg", hero_svg('FRANCHISE', 'LEEFRIENDS 창업', '사계절 디저트 카페, 함께 시작하세요', $palettes[3]));

// ---- Brand ----
file_put_contents("$root/brand/story.svg", card_svg('진심을 담은 한 그릇', 'SINCE 2026', $palettes[0], '🥭'));
file_put_contents("$root/brand/quality.svg", card_svg('엄선한 애플망고', 'FRESH QUALITY', $palettes[3], '🌱'));
file_put_contents("$root/brand/space.svg", card_svg('머무르고 싶은 공간', 'COZY SPACE', $palettes[4], '🏠'));

// ---- Store sample photo ----
file_put_contents("$root/store/default.svg", card_svg('LEEFRIENDS', 'STORE', $palettes[2], '🏬'));

// ---- OG / logo mark ----
file_put_contents("$root/og.svg", hero_svg('LEEFRIENDS', '리프렌즈', '프리미엄 망고빙수 전문점', $palettes[1]));

echo "Generated images under public/images\n";
