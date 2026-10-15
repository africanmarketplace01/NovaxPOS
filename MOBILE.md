# Novax Mobile — Flutter workspace

Two apps that share one code core, following the marketplace industry standard
(consumer app vs provider app) while single-sourcing everything reusable.

```
NovaxPOS/
├── novax_core/              # shared package: theme, ApiClient, PushService, ChatView, widgets
├── novax_marketplace_app/   # CUSTOMER app  (depends on novax_core via path)
└── novax_vendor_app/        # VENDOR app    (depends on novax_core via path)
```

## Why this structure
- **Separate binaries** for shoppers vs vendors — different audiences, permissions,
  store listings, release cadence, and security surface (Uber/Uber-Driver pattern).
- **One shared core** (`novax_core`) removes duplication: the theme, the HTTP client
  (base URL + Bearer token + multipart), the OneSignal `PushService`, the polling
  `ChatView`, and common widgets (`StatCard`, `StatusPill`, `EmptyState`) live once.
  Each app keeps only its own screens/state/models.
- Sharing is via **path dependencies** (`novax_core: {path: ../novax_core}`) — no extra
  tooling. (You can later formalise it as a Dart pub workspace or Melos if desired.)

## Run
```bash
# once per app: scaffold platform folders around the existing lib/
cd novax_marketplace_app && flutter create . && flutter pub get && flutter run
cd novax_vendor_app     && flutter create . && flutter pub get && flutter run
```
`flutter pub get` resolves `novax_core` automatically from the relative path.

## "Very fast app" checklist (already applied + build flags)
Applied in the code:
- `const` constructors throughout (fewer rebuilds; the analyzer enforces it).
- `ListView.builder` / `GridView.builder` everywhere (lazy, virtualized lists) with
  infinite-scroll pagination on the product grid.
- `cached_network_image` for all remote images (memory + disk cache).
- `IndexedStack` bottom nav so tab state is preserved (no rebuild on tab switch).
- Local persistence (token, cart) so cold starts are instant and offline-friendly.
- Chat via lightweight 4s polling (no socket connection cost); swap to WebSockets later
  if you need sub-second latency.

Build for speed / small size:
```bash
# Android: split per ABI → much smaller downloads, faster install
flutter build apk --release --split-per-abi
flutter build appbundle --release          # Play Store (Play does the splitting)

# iOS
flutter build ios --release
```
Both `--release` builds run AOT-compiled native code (not the debug JIT), tree-shake
unused icons, and minify. For first-frame speed, keep `main()` light (we only restore
the session + init push) and avoid heavy work before `runApp`.

## Configuration
- Customer: `novax_marketplace_app/lib/config.dart` → `apiBaseUrl`, `oneSignalAppId`.
- Vendor: `novax_vendor_app/lib/config.dart` → `rootDomain`, `oneSignalAppId`.
- Push: set the OneSignal keys in the superadmin SaaS settings (backend already reads
  them) and the App IDs in each app's config.
