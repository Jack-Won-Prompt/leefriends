# -*- coding: utf-8 -*-
"""
Generates a raster (PNG) Open Graph image for LEEFRIENDS.
SVG og:images fail on many link crawlers (Kakao, iMessage, Facebook) and the
old og.svg drew the mango as a raw emoji, which renders as a U+1F96D tofu box
on devices without a color-emoji font. This draws everything as raster + a
vector mango so it renders identically everywhere.

Run: python scripts/gen_og.py
"""
import os
from PIL import Image, ImageDraw, ImageFont

W, H = 1200, 630
root = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "public", "images")
os.makedirs(root, exist_ok=True)

BOLD = r"C:\Windows\Fonts\malgunbd.ttf"
REG  = r"C:\Windows\Fonts\malgun.ttf"


def lerp(a, b, t):
    return tuple(int(a[i] + (b[i] - a[i]) * t) for i in range(3))


# --- diagonal gradient background: #FFF3C4 -> #FFD15C -> #FF9F1C ---
c1, c2, c3 = (0xFF, 0xF3, 0xC4), (0xFF, 0xD1, 0x5C), (0xFF, 0x9F, 0x1C)
img = Image.new("RGB", (W, H), c1)
px = img.load()
for y in range(H):
    for x in range(W):
        t = (x / W + y / H) / 2.0
        col = lerp(c1, c2, t / 0.5) if t < 0.5 else lerp(c2, c3, (t - 0.5) / 0.5)
        px[x, y] = col

draw = ImageDraw.Draw(img, "RGBA")

# soft decorative circles
draw.ellipse([940, -110, 1470, 420], fill=(255, 255, 255, 30))
draw.ellipse([80, 470, 520, 910], fill=(255, 255, 255, 26))
draw.ellipse([1010, 430, 1250, 670], fill=(255, 255, 255, 36))

# --- vector mango (body + leaf), replaces the emoji ---
cx, cy = 960, 300
draw.ellipse([cx - 165, cy - 130, cx + 145, cy + 150],
             fill=(0xF1, 0x84, 0x00, 255))          # mango body
draw.ellipse([cx - 130, cy - 100, cx + 20, cy + 40],
             fill=(0xFF, 0xC5, 0x4D, 90))            # highlight
# leaf
draw.polygon([(cx + 60, cy - 150), (cx + 150, cy - 210), (cx + 120, cy - 120)],
             fill=(0x3E, 0x9E, 0x4E, 255))
draw.line([(cx + 40, cy - 120), (cx + 110, cy - 175)], fill=(0x2E, 0x7D, 0x32, 255), width=6)

# --- text ---
kicker = ImageFont.truetype(BOLD, 34)
title  = ImageFont.truetype(BOLD, 120)
sub    = ImageFont.truetype(REG, 42)

x0 = 110
draw.text((x0, 210), "L E E F R I E N D S", font=kicker, fill=(255, 255, 255, 235))
draw.text((x0, 258), "리프렌즈", font=title, fill=(255, 255, 255, 255))
draw.text((x0, 410), "프리미엄 망고빙수 전문점", font=sub, fill=(255, 255, 255, 235))

out = os.path.join(root, "og.png")
img.save(out, "PNG", optimize=True)
print("wrote", out, os.path.getsize(out), "bytes")
