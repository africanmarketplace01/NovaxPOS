# Novax Vendor — Mobile App (Flutter)

Vendor/tenant companion app for NovaxSuites. Vendors sign into their own store
(tenant subdomain) and manage the marketplace-facing "majors" on the go.

## Features
- **Login** — store subdomain + email/username + password → Sanctum token
  (`POST /api/vendor/login`).
- **Dashboard** — today's sales & revenue, month revenue, pending orders,
  low-stock count, recent orders.
- **Orders** — marketplace/store orders with status filter and one-tap status
  updates (pending → processing → shipped → delivered / cancelled).
- **Products & stock** — search, low-stock filter, quick edit of stock & price.
- **Sourcing requests** — open customer requests, respond with an offer/price,
  and a live chat with the customer.
- **Reports** — stock value, out-of-stock count, top-selling products.
- **Push notifications** (OneSignal) — new orders, new sourcing requests, and
  new chat messages.

## Backend API
Consumes the tenant API added in `routes/api.php` (`api/vendor/*`,
`VendorAppController`). Vendor login issues a Sanctum token; all other calls use
`Authorization: Bearer <token>`.

## Setup
```bash
cd novax_vendor_app
flutter create .        # scaffold android/ ios/ around the existing lib/
flutter pub get
```
- Set your platform root domain in `lib/config.dart` → `Config.rootDomain`.
- For push, set `Config.oneSignalAppId` (from OneSignal) and complete the
  OneSignal Flutter setup (Android `google-services.json`, iOS APNs key). Push
  is a no-op if the App ID is empty, so the app still runs without it.

## Run / build
```bash
flutter run
flutter build apk --release
flutter build appbundle --release   # Google Play
flutter build ios --release         # App Store (archive in Xcode)
```

## Notes
- Only **admin/owner** role users can sign in (the API enforces this).
- The API base is built as `https://<store>.<rootDomain>/api`. During local dev,
  change `Config.apiBaseFor` to your local URL (e.g. `http://10.0.2.2:8000/api`).
