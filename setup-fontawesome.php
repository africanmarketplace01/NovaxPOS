<?php
/**
 * NovaxPOS — Font Awesome 6.5 Self-Host Setup
 * Run from project root:  php setup-fontawesome.php
 */

$version   = '6.5.0';
$base      = "https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@{$version}";
$cssDir    = __DIR__ . '/public/fontawesome/css';
$webfontDir = __DIR__ . '/public/fontawesome/webfonts';

// Webfont files needed for all FA icons
$webfonts = [
    'fa-brands-400.woff2',
    'fa-brands-400.ttf',
    'fa-regular-400.woff2',
    'fa-regular-400.ttf',
    'fa-solid-900.woff2',
    'fa-solid-900.ttf',
    'fa-v4compatibility.woff2',
    'fa-v4compatibility.ttf',
];

function download(string $url, string $dest): bool
{
    echo "  Downloading " . basename($dest) . "... ";
    $ctx = stream_context_create(['http' => [
        'timeout'       => 30,
        'user_agent'    => 'NovaxPOS-FA-Setup/1.0',
        'ignore_errors' => true,
    ]]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 100) {
        echo "FAILED (check internet access)\n";
        return false;
    }
    file_put_contents($dest, $data);
    echo "OK (" . number_format(strlen($data)) . " bytes)\n";
    return true;
}

// ── 1. Create directories ───────────────────────────────────
echo "[1/3] Creating directories...\n";
foreach ([$cssDir, $webfontDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "  Created: $dir\n";
    } else {
        echo "  Exists:  $dir\n";
    }
}

// ── 2. Download CSS ─────────────────────────────────────────
echo "\n[2/3] Downloading CSS...\n";
$cssDest = $cssDir . '/all.min.css';
if (!download("{$base}/css/all.min.css", $cssDest)) {
    exit(1);
}

// Fix webfont path: jsDelivr CSS uses ../webfonts/, asset() serves from /fontawesome/
// The relative path ../webfonts/ works fine when CSS is at /fontawesome/css/all.min.css
// (resolves to /fontawesome/webfonts/) — no rewrite needed.

// ── 3. Download webfonts ────────────────────────────────────
echo "\n[3/3] Downloading webfonts...\n";
$failed = 0;
foreach ($webfonts as $font) {
    if (!download("{$base}/webfonts/{$font}", "{$webfontDir}/{$font}")) {
        $failed++;
    }
}

echo "\n";
if ($failed === 0) {
    echo "Done! Font Awesome {$version} is now self-hosted at public/fontawesome/\n";
    echo "No CDN dependencies remain — tracking prevention warning is gone.\n";
} else {
    echo "Completed with {$failed} failure(s). Re-run to retry failed files.\n";
}
