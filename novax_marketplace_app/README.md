# NovaxSuites POS & Marketplace — Mobile App (Flutter)

Customer shopping app for the NovaxSuites POS & global marketplace. It talks to the
Laravel backend's **Marketplace API v1** (`/api/marketplace/v1`, see
`routes/marketplace-api.php` in the POS project).

## What's included (customer app)
- **Auth** — register / login / persisted session (Bearer token in `SharedPreferences`).
- **Shop** — product grid with search + infinite scroll.
- **Product detail** — image gallery, price/discount, stock, description, ratings & reviews.
- **Cart** — add / update qty / remove, persisted locally.
- **Checkout** — delivery details + payment method, places the order via the API
  (fans out to the vendor POS and notifies vendors, same as the web checkout).
- **Orders** — order history + **live order-tracking timeline** and full details.
- **Account** — profile + sign out.

State: `provider`. Networking: `http`. Images: `cached_network_image`.
Theme mirrors the SaaS brand colors (`lib/theme.dart`).

## Prerequisites
- Flutter SDK 3.3+ (`flutter --version`)
- Android Studio / Xcode for device builds

## First-time setup
This repo ships `lib/`, `pubspec.yaml` and config only. Generate the platform
folders (android/ios/etc.) and install packages:

```bash
cd novax_marketplace_app
flutter create .          # scaffolds android/, ios/, etc. around the existing lib/
flutter pub get
```

## Point the app at your backend
Edit `lib/config.dart` → `Config.apiBaseUrl`:

```dart
// Production
static const String apiBaseUrl = 'https://novasuitespos.com/api/marketplace/v1';

// Local dev:
//   Android emulator -> http://10.0.2.2:8000/api/marketplace/v1
//   iOS simulator    -> http://127.0.0.1:8000/api/marketplace/v1
```

## Run
```bash
flutter run                # on a connected device / emulator
```

## Build store binaries
```bash
flutter build apk --release            # Android APK
flutter build appbundle --release      # Google Play (.aab)
flutter build ios --release            # iOS (then archive in Xcode for App Store)
```
Publishing needs your own signing keys, bundle IDs, and Play/App Store developer
accounts. Set the app id in `android/app/build.gradle` (applicationId) and the
iOS bundle identifier in Xcode.

## Backend requirements
- Run the marketplace migrations (`php artisan landlord:migrate`) so
  `marketplace_customer_tokens`, orders, and tracking tables exist.
- The API must be reachable over HTTPS from devices.

## Project structure
```
lib/
  config.dart            API base URL + app constants
  theme.dart             SaaS-brand Material theme
  main.dart              providers + bottom-nav shell
  services/api_client.dart   HTTP + Bearer token
  state/                 auth_provider, cart_provider
  models/                product, order
  screens/               login, register, home, product, cart,
                         checkout, orders, order_detail, account
  widgets/product_card.dart
```

## Roadmap (next milestones)
1. **Sourcing requests + chat** (customer): screens for posting a request with
   photo/short video and the live vendor chat — API already exists
   (`/requests`, `/threads/{id}/messages`).
2. **Push notifications** (FCM/APNs) for order status + new chat messages.
3. **Vendor app** (tenant majors): incoming orders, sourcing + chat, sales
   dashboard, products & stock, quick POS sale, product/warehouse reports.
   This needs a **vendor API** built on the tenant Sanctum API in
   `routes/api.php`.
4. Payment gateway integration in-app (Stripe/Paystack) for online payment
   methods (checkout currently supports Cash on Delivery / Bank transfer).
```
