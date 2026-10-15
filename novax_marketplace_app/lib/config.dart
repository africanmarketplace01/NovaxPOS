/// App-wide configuration.
///
/// Point [apiBaseUrl] at your central domain. All marketplace API routes live
/// under /api/marketplace/v1 (see routes/marketplace-api.php in the backend).
class Config {
  // For a physical device / production use your public domain:
  static const String apiBaseUrl = 'https://novasuitespos.com/api/marketplace/v1';

  /// Central web domain — used to embed existing web portals (superadmin, SaaS
  /// public pages) in a WebView instead of rebuilding them natively.
  static const String webBaseUrl = 'https://novasuitespos.com';

  /// Platform root domain — a new store "acme" lives at acme.novasuitespos.com.
  static const String rootDomain = 'novasuitespos.com';

  // Handy overrides while developing locally:
  //   Android emulator -> http://10.0.2.2:8000/api/marketplace/v1
  //   iOS simulator    -> http://127.0.0.1:8000/api/marketplace/v1
  // static const String apiBaseUrl = 'http://10.0.2.2:8000/api/marketplace/v1';

  static const String appName = 'Marketplace';

  /// Fallback currency label if the API doesn't return one.
  static const String currency = 'USD';

  /// OneSignal App ID (from your OneSignal dashboard). Empty = push disabled.
  static const String oneSignalAppId = 'c0d024d5-6f2f-46fd-8485-c55ac9918b8b'; 
}
