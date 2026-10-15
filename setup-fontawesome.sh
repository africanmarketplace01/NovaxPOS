#!/usr/bin/env bash
# ============================================================
# NovaxPOS — Font Awesome 6.5 Self-Host Setup
# Run once from your project root:  bash setup-fontawesome.sh
# ============================================================

set -e

echo "[1/4] Installing @fortawesome/fontawesome-free via npm..."
npm install @fortawesome/fontawesome-free --save-dev

echo "[2/4] Creating public/fontawesome/ directories..."
mkdir -p public/fontawesome/css
mkdir -p public/fontawesome/webfonts

echo "[3/4] Copying CSS and webfonts..."
cp node_modules/@fortawesome/fontawesome-free/css/all.min.css public/fontawesome/css/all.min.css
cp node_modules/@fortawesome/fontawesome-free/webfonts/*.woff2 public/fontawesome/webfonts/
cp node_modules/@fortawesome/fontawesome-free/webfonts/*.woff  public/fontawesome/webfonts/ 2>/dev/null || true
cp node_modules/@fortawesome/fontawesome-free/webfonts/*.ttf   public/fontawesome/webfonts/ 2>/dev/null || true

echo "[4/4] Done. Font Awesome is now served from public/fontawesome/"
echo "      All views already updated to use: asset('fontawesome/css/all.min.css')"
