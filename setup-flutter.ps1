# ============================================================================
#  setup-flutter.ps1 — one-time Flutter toolchain setup for Windows
# ----------------------------------------------------------------------------
#  Clones the Flutter SDK, adds it to the *user* PATH permanently, and runs
#  `flutter doctor`. Run in PowerShell:
#
#     powershell -ExecutionPolicy Bypass -File .\setup-flutter.ps1
#
#  Optional: -InstallDir "D:\dev\flutter"   -Channel "stable"
#  Note: the Android SDK / Android Studio must still be installed separately
#  (see the notes printed at the end). iOS builds require a Mac + Xcode.
# ============================================================================
param(
  [string]$InstallDir = "C:\src\flutter",
  [string]$Channel    = "stable"
)

$ErrorActionPreference = "Stop"
Write-Host "Flutter setup starting..." -ForegroundColor Cyan

# 1) Git present?
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
  Write-Host "Git is not installed or not on PATH." -ForegroundColor Red
  Write-Host "Install Git (Laragon includes it) or add it to PATH, then re-run." -ForegroundColor Yellow
  exit 1
}

# 2) Clone (or update) the Flutter SDK
if (Test-Path (Join-Path $InstallDir "bin\flutter.bat")) {
  Write-Host "Flutter already present at $InstallDir — pulling latest ($Channel)..." -ForegroundColor Yellow
  Push-Location $InstallDir
  git checkout $Channel
  git pull
  Pop-Location
} else {
  Write-Host "Cloning Flutter ($Channel) into $InstallDir ..." -ForegroundColor Cyan
  git clone https://github.com/flutter/flutter.git -b $Channel $InstallDir
}

$flutterBin = Join-Path $InstallDir "bin"

# 3) Add to this session's PATH
if ($env:Path -notlike "*$flutterBin*") { $env:Path = "$flutterBin;$env:Path" }

# 4) Add to the *user* PATH permanently (no admin needed)
$userPath = [Environment]::GetEnvironmentVariable("Path", "User")
if ($userPath -notlike "*$flutterBin*") {
  [Environment]::SetEnvironmentVariable("Path", "$flutterBin;$userPath", "User")
  Write-Host "Added $flutterBin to your user PATH (permanent)." -ForegroundColor Green
} else {
  Write-Host "$flutterBin already on your user PATH." -ForegroundColor Yellow
}

# 5) Warm up + diagnostics
Write-Host "`nRunning flutter --version ..." -ForegroundColor Cyan
& "$flutterBin\flutter.bat" --version

Write-Host "`nRunning flutter doctor ..." -ForegroundColor Cyan
& "$flutterBin\flutter.bat" doctor

Write-Host "`n============================================================" -ForegroundColor Cyan
Write-Host "Done. IMPORTANT next steps:" -ForegroundColor Green
Write-Host "  1. Open a NEW terminal (so the permanent PATH takes effect)."
Write-Host "  2. For Android builds: install Android Studio (bundles the Android SDK + JDK),"
Write-Host "     then in a new terminal run:  flutter doctor --android-licenses"
Write-Host "  3. iOS builds require a Mac + Xcode (cannot be done on Windows)."
Write-Host "  4. Then build an app, e.g.:"
Write-Host "       cd novax_marketplace_app; flutter create .; flutter pub get; flutter run"
Write-Host "============================================================" -ForegroundColor Cyan
