# Deployment Guide — NovaxSuites POS, Marketplace & Mobile Apps

Three deployables: the **Laravel backend**, the **customer app**, and the **vendor app**.

---

## 1. Backend (Laravel — `novasuitespos.com`)

### Every deploy
Run `./deploy.sh` from the project root **or** the steps manually:

```bash
composer install --no-dev --optimize-autoloader        # if vendor/ not shipped
php artisan optimize:clear
php artisan storage:link                                # once; idempotent
php artisan landlord:migrate --force                    # central/landlord tables
php artisan migrate --force                             # default connection
php artisan config:cache
php artisan event:cache
php artisan view:cache
php artisan queue:restart
```

> ⚠️ **Never run `php artisan route:cache` or `php artisan optimize`.**
> This app has intentional domain-separated duplicate route names (e.g. `register`
> on both the central and tenant trees), which route caching cannot serialize.
> Config/event/view caching give you nearly all the performance benefit.

### One-time / infrastructure
- **HTTPS** on the root domain **and** tenant subdomains (wildcard cert). Stores reject
  cleartext API calls.
- **Queue worker** (Supervisor/systemd) for notification jobs:
  `php artisan queue:work --sleep=3 --tries=3`
- **Scheduler cron:** `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
- **PHP limits** for sourcing video uploads: `upload_max_filesize=80M`, `post_max_size=85M`,
  and adequate `max_execution_time` / `memory_limit`.

### Runtime config (no redeploy — set in Superadmin → SaaS Settings)
- **Mail (SMTP)** — used by order/sourcing emails.
- **OneSignal** — `onesignal_app_id` + `onesignal_rest_api_key` (push targeting).
- **Marketplace fees / delivery agencies / promotions** — managed in the superadmin UI.

### Post-deploy smoke test
- `GET https://novasuitespos.com/api/marketplace/v1/products` returns JSON.
- Superadmin → Promotions loads; landing page renders.
- Place a test marketplace order → appears in the vendor POS; customer can track it.

---

## 2. Mobile apps (Flutter → Google Play + App Store)

Shared code lives in `novax_core/`; the two apps depend on it via path.

### 2.0 Install the Flutter toolchain (build machine — do this first)
`flutter: command not found / not recognized` means the SDK isn't installed or isn't on PATH.

**Windows (quickest):** run the helper from the project root:
```powershell
powershell -ExecutionPolicy Bypass -File .\setup-flutter.ps1
```
It clones the Flutter SDK to `C:\src\flutter`, adds `C:\src\flutter\bin` to your user PATH
permanently, and runs `flutter doctor`. **Open a new terminal afterwards** so the PATH takes effect.

Manual (any OS):
1. Install the **Flutter SDK** from flutter.dev (or `git clone -b stable https://github.com/flutter/flutter.git C:\src\flutter`).
2. Add `<flutter>/bin` to PATH (Windows: System Environment Variables → user `Path` → add
   `C:\src\flutter\bin`).
3. **Android builds:** install **Android Studio** (bundles the Android SDK + JDK), install the
   SDK Platform + **Command-line Tools** in its SDK Manager, then `flutter doctor --android-licenses`.
4. **iOS builds:** require **macOS + Xcode** — cannot be built on Windows.
5. Verify: `flutter --version` and `flutter doctor` (fix any ✗). A device/emulator must be
   running for `flutter run`.

### One-time setup (per app)
```bash
cd novax_marketplace_app     # or novax_vendor_app
flutter create .             # generates android/ ios/ around the existing lib/
flutter pub get              # resolves novax_core from ../novax_core
```
Then:
1. **Bundle IDs** — e.g. `com.novaxsuites.marketplace` and `com.novaxsuites.vendor`
   (Android: `applicationId` in `android/app/build.gradle`; iOS: Xcode → Signing).
2. **App config** —
   - Customer: `lib/config.dart` → `apiBaseUrl`, `oneSignalAppId`.
   - Vendor: `lib/config.dart` → `rootDomain`, `oneSignalAppId`.
3. **Push (OneSignal)** — in the OneSignal dashboard add Android (upload your **FCM**
   server key) and iOS (**APNs** auth key). Add `google-services.json` (Android) and
   configure APNs (iOS). Push is a no-op if `oneSignalAppId` is empty, so apps still run.
4. **Signing** — Android upload keystore; iOS distribution cert + provisioning profile.
5. **App icons / splash** — set per app (e.g. `flutter_launcher_icons`).

### Build release artifacts
```bash
# Android
flutter build appbundle --release            # → build/app/outputs/bundle/release/*.aab  (Play)
flutter build apk --release --split-per-abi   # smaller sideload APKs (optional)

# iOS
flutter build ios --release                   # then open Runner.xcworkspace → Archive → distribute
```

### Submit
- **Google Play Console** → create app → upload `.aab` → complete listing (screenshots,
  privacy policy, data safety) → submit for review.
- **App Store Connect** → create app → upload build via Xcode/Transporter → complete
  listing → submit for review.

Matrix: **2 apps × 2 stores = 4 listings.** You can launch Android first if desired.

> **Vendor app is one binary for all stores.** Each store owner enters their subdomain at
> login; the app builds the API base as `https://<store>.<rootDomain>/api`.

---

## 3. Release cadence
- **Backend:** deploy anytime (`./deploy.sh`) — no store review.
- **Apps:** each change → new build → store review (hours–2 days). Because theme,
  networking, push and chat live in `novax_core`, a shared change means rebuilding both
  apps once. Bump `version:` in each `pubspec.yaml` (e.g. `1.0.1+2`) before every store build.

## 4. Rollback
- **Backend:** redeploy the previous release tag + `php artisan optimize:clear` and the
  cache rebuild steps. Migrations are additive/guarded; avoid `migrate:rollback` in prod
  unless a specific migration is known-safe to reverse.
- **Apps:** in the store console, halt the rollout / promote the previous release.
