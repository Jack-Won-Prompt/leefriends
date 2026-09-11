#!/usr/bin/env bash
#
# leefriends 배포 스크립트 — 운영 서버(~/www/leefriends)에서 실행.
#   git pull(main) → 캐시 정리/재생성 → 마이그레이션.
# 사용: cd ~/www/leefriends && bash deploy.sh
#
set -euo pipefail
cd "$(dirname "$0")"

echo "[deploy] git pull origin main"
git pull origin main

echo "[deploy] artisan caches"
php artisan config:clear
php artisan route:clear
php artisan route:cache          # 운영은 라우트 캐시 사용 — 새 라우트 반영에 필수
php artisan view:clear

echo "[deploy] migrate"
php artisan migrate --force

echo "[deploy] done: $(git rev-parse --short HEAD)"
